<?php
namespace Trustpilot\Reviews\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Trustpilot\Reviews\Helper\OrderData;
use Trustpilot\Reviews\Helper\Data;
use Trustpilot\Reviews\Helper\TrustpilotHttpClient;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Store\Model\ScopeInterface as StoreScopeInterface;
use Trustpilot\Reviews\Helper\TrustpilotLog;

define('__ACCEPTED__', 202);

class OrderSaveObserver implements ObserverInterface
{   

    protected $_trustpilotHttpClient;
    protected $_orderData;
    protected $_helper;
    protected $_config;
    protected $_trustpilotLog;
    
    public function __construct(
        TrustpilotHttpClient $trustpilotHttpClient,
        OrderData $orderData,
        Data $helper,
        Config $config,
        TrustpilotLog $trustpilotLog)
    {
        $this->_helper = $helper;
        $this->_trustpilotHttpClient = $trustpilotHttpClient;
        $this->_orderData = $orderData;
        $this->_config = $config;
        $this->_trustpilotLog = $trustpilotLog;
    }
  
    public function execute(EventObserver $observer) 
    {
        $event = $observer->getEvent();
        $order = $event->getOrder();
        $orderStatus = $order->getState();
        $storeId = $order->getStoreId();

        $settings = json_decode($this->_helper->getConfig('master_settings_field', $storeId, StoreScopeInterface::SCOPE_STORES));
        $key = $settings->general->key;

        try {
            if (isset($key) && $order->getState() != $order->getOrigData('state')) {
                $data = $this->_orderData->getInvitation($order, 'sales_order_save_after', \Trustpilot\Reviews\Model\Config::WITHOUT_PRODUCT_DATA);

                if (in_array($orderStatus, $settings->general->mappedInvitationTrigger)) {
                    $response = $this->_trustpilotHttpClient->postInvitation($key, $storeId, $data);

                    if ($response['code'] == __ACCEPTED__) {
                        $data = $this->_orderData->getInvitation($order, 'sales_order_save_after', \Trustpilot\Reviews\Model\Config::WITH_PRODUCT_DATA);
                        $response = $this->_trustpilotHttpClient->postInvitation($key, $storeId, $data);
                    }
                    $this->handleSingleResponse($response, $data, $storeId);
                } else {
                    $data['payloadType'] = 'OrderStatusUpdate';
                    $this->_trustpilotHttpClient->postInvitation($key, $storeId, $data);
                }
            }
        } catch (\Throwable $e) {
            $description = 'Unable to get invitation data in OrderSaveObserver';
            $vars = array(
                'storeId' => isset($storeId) ? $storeId : null,
                'orderStatus' => isset($orderStatus) ? $orderStatus : null,
                'key' => isset($key) ? $key : null,
            );
            $this->_trustpilotLog->error($e, $description, $vars);
        } catch (\Exception $e) {
            $description = 'Unable to get invitation data in OrderSaveObserver';
            $vars = array(
                'storeId' => isset($storeId) ? $storeId : null,
                'orderStatus' => isset($orderStatus) ? $orderStatus : null,
                'key' => isset($key) ? $key : null,
            );
            $this->_trustpilotLog->error($e, $description, $vars);
        }
    }

    public function handleSingleResponse($response, $order, $storeId)
    {
        try {
            $scope = StoreScopeInterface::SCOPE_STORES;
            $synced_orders = (int) $this->_helper->getConfig('past_orders', $storeId, $scope);
            $failed_orders = json_decode($this->_helper->getConfig('failed_orders', $storeId, $scope));

            if ($response['code'] == 201) {
                if (isset($failed_orders->{$order['referenceId']})) {
                    unset($failed_orders->{$order['referenceId']});
                    $this->saveConfig('failed_orders', json_encode($failed_orders), $scope, $storeId);
                }
            } else {
                $failed_orders->{$order['referenceId']} = base64_encode('Automatic invitation sending failed');
                $this->saveConfig('failed_orders', json_encode($failed_orders), $scope, $storeId);
            }
        } catch (\Throwable $e) {
            $description = 'Unable to handle response from invitations API';
            $this->_trustpilotLog->error($e, $description, array('storeId' => $storeId));
        } catch (\Exception $e) {
            $description = 'Unable to handle response from invitations API';
            $this->_trustpilotLog->error($e, $description, array('storeId' => $storeId));
        }
    }

    private function saveConfig($config, $value, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0)
    {
        $path = 'trustpilot/trustpilot_general_group/';

        if ($scope === 'store') {
            $scope = 'stores';
        } elseif ($scope === 'website') {
            $scope = 'websites';
        }

        $this->_config->saveConfig($path . $config,  $value, $scope, $scopeId);
    }
}
