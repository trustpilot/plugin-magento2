<?php
namespace Trustpilot\Reviews\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use \Psr\Log\LoggerInterface;
use Trustpilot\Reviews\Helper\OrderData;
use Trustpilot\Reviews\Helper\Data;
use Magento\Framework\App\ProductMetadataInterface;
use Trustpilot\Reviews\Helper\TrustpilotHttpClient;

define('__ACCEPTED__', 202);

class OrderSaveObserver implements ObserverInterface
{   

    protected $_trustpilotHttpClient;
    protected $_logger;
    protected $_productMetadata;
    protected $_orderDataHelper;
    protected $_dataHelper;
    protected $_storeManager;
    
    public function __construct(
        LoggerInterface $logger, 
        TrustpilotHttpClient $trustpilotHttpClient,
        OrderData $orderDataHelper,
        ProductMetadataInterface $productMetadata,
        Data $dataHelper)                       
    {
        $this->_dataHelper = $dataHelper;
        $this->_trustpilotHttpClient = $trustpilotHttpClient;
        $this->_logger = $logger; 
        $this->_orderDataHelper = $orderDataHelper;
        $this->_productMetadata = $productMetadata;  
        $this->_version = $this->_dataHelper->getGeneralConfigValue('ReleaseNumber');
    }
  
    public function execute(EventObserver $observer) 
    {
        $order = $observer->getEvent()->getOrder();
        $storeId = $order->getStoreId();
        $orderStatusId = $order->getState();
        try {
            $key = trim($this->_dataHelper->getGeneralConfigValue('key'));
            $data = [
                'referenceId' => $order->getRealOrderId(),
                'source' => 'Magento-' . $this->_productMetadata->getVersion(),
                'pluginVersion' => $this->_version,
                'orderStatusId' => $orderStatusId ,
                'orderStatusName' => $order->getStatusLabel(),
                'hook' => 'sales_order_save_after'
            ];
            if ($orderStatusId == \Magento\Sales\Model\Order::STATE_NEW || $orderStatusId == null) {
                    $data['recipientEmail'] = trim($this->_orderDataHelper->getEmail($order));
                    $data['recipientName'] = $order->getCustomerName();
                $response = $this->_trustpilotHttpClient->postInvitation($key, $storeId, $data);
                if ($response['code'] == __ACCEPTED__) {
                    $products = $this->_orderDataHelper->getProducts($order);
                    $data['products'] = $products;
                    $data['productSkus'] = $this->_orderDataHelper->getSkus($products);
                    $this->_trustpilotHttpClient->postInvitation($key, $storeId, $data);
                }
            } else {
                $data['payloadType'] = 'OrderStatusUpdate';
                $this->_trustpilotHttpClient->postInvitation($key, $storeId, $data);
            }
            return;
        } catch (\Exception $e) {
            $error = ['message' => $e->getMessage()];
            $data = ['error' => $error];
            $this->_trustpilotHttpClient->postInvitation($key, $storeId, $data);
            return;
        }            
    }
}
