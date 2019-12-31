<?php
namespace Trustpilot\Reviews\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template;
use Trustpilot\Reviews\Helper\Data;
use Trustpilot\Reviews\Helper\TrustpilotPluginStatus;
use Trustpilot\Reviews\Helper\OrderData;
use Magento\Store\Model\ScopeInterface as StoreScopeInterface;
use Trustpilot\Reviews\Helper\TrustpilotLog;
use \Magento\Framework\UrlInterface;

class Success extends Template
{
    protected $_salesFactory;
    protected $_checkoutSession;
    protected $_helper;
    protected $_orderData;
    protected $_trustpilotLog;
    protected $_storeManager;
    protected $_pluginStatus;

    public function __construct(
        Context $context,
        Order $salesOrderFactory,
        Session $checkoutSession,
        Data $helper,
        OrderData $orderData,
        TrustpilotLog $trustpilotLog,
        array $data = [],
        TrustpilotPluginStatus $pluginStatus)
    {
        $this->_salesFactory = $salesOrderFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_helper = $helper;
        $this->_orderData = $orderData;
        $this->_trustpilotLog = $trustpilotLog;
        $this->_storeManager = $context->getStoreManager();
        $this->_pluginStatus = $pluginStatus;

        parent::__construct($context, $data);
    }

    public function getOrder()
    {
        try {
            $orderId = $this->_checkoutSession->getLastOrderId();
            $order   = $this->_salesFactory->load($orderId);
            $storeId = $order->getStoreId();
            
            $origin = $this->_storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_WEB);
            $code = $this->_pluginStatus->checkPluginStatus($origin, $storeId);
            if ($code > 250 && $code < 254) {
                return 'undefined';
            }
            
            $general_settings = json_decode($this->_helper->getConfig('master_settings_field', $storeId, StoreScopeInterface::SCOPE_STORES))->general;
            $data = $this->_orderData->getInvitation($order, 'magento2_success', \Trustpilot\Reviews\Model\Config::WITH_PRODUCT_DATA);

            try {
                $data['totalCost'] = $order->getGrandTotal();
                $data['currency'] = $order->getOrderCurrencyCode();
            } catch (\Throwable $e) {
                $description = 'Unable to get order total cost';
                $this->_trustpilotLog->error($e, $description, array(
                    'orderId' => $orderId,
                    'storeId' => $storeId
                ));
            } catch (\Exception $e) {
                $description = 'Unable to get order total cost';
                $this->_trustpilotLog->error($e, $description, array(
                    'orderId' => $orderId,
                    'storeId' => $storeId
                ));
            }

            if (!in_array('trustpilotOrderConfirmed', $general_settings->mappedInvitationTrigger)) {
                $data['payloadType'] = 'OrderStatusUpdate';
            }

            return json_encode($data, JSON_HEX_APOS);
        } catch (\Throwable $e) {
            $error = array('message' => $e->getMessage());
            $data = array('error' => $error);
            $vars = array(
                'orderId' => isset($orderId) ? $orderId : null,
                'storeId' => isset($storeId) ? $storeId : null,
            );
            $description = 'Unable to get order data';
            $this->_trustpilotLog->error($e, $description, $vars);
            return json_encode($data, JSON_HEX_APOS);
        } catch (\Exception $e) {
            $error = array('message' => $e->getMessage());
            $data = array('error' => $error);
            $vars = array(
                'orderId' => isset($orderId) ? $orderId : null,
                'storeId' => isset($storeId) ? $storeId : null,
            );
            $description = 'Unable to get order data';
            $this->_trustpilotLog->error($e, $description, $vars);
            return json_encode($data, JSON_HEX_APOS);
        }            
    }
}
