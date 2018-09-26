<?php
namespace Trustpilot\Reviews\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template;
use Trustpilot\Reviews\Helper\Data;
use Trustpilot\Reviews\Helper\Notifications;
use Trustpilot\Reviews\Helper\OrderData;

class Success extends Template
{
    protected $_salesFactory;
    protected $_checkoutSession;
    protected $_helper;
    protected $_orderDataHelper;
    protected $_notifications;

    public function __construct(
        Context $context,
        Order $salesOrderFactory,
        Session $checkoutSession,
        Data $helper,
        OrderData $orderDataHelper,
        Notifications $notifications,
        array $data = []
    ) {
        $this->_salesFactory = $salesOrderFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_helper = $helper;
        $this->_orderDataHelper = $orderDataHelper;
        $this->_notifications = $notifications;

        parent::__construct($context, $data);
    }

    public function getOrder()
    {
        try {
            $orderId = $this->_checkoutSession->getLastOrderId();
            $order   = $this->_salesFactory->load($orderId);
            $products = $this->_orderDataHelper->getProducts($order);

            $data = [
                'recipientEmail' => trim($this->getEmail($order)),
                'recipientName' => trim($this->_orderDataHelper->getName($order)),
                'referenceId' => $order->getRealOrderId(),
                'productSkus' => $this->_orderDataHelper->getSkus($products),
                'source' => 'Magento-'.$this->_helper->getVersion(),
                'pluginVersion' => $this->_helper->getGeneralConfigValue('ReleaseNumber'),
                'products' => $products,
            ];

            return json_encode($data, JSON_HEX_APOS);
        } catch (\Exception $e) {
            $error = ['message' => $e->getMessage()];
            $data = ['error' => $error];
            return json_encode($data, JSON_HEX_APOS);
        }            
    }

    public function getLastRealOrder()
    {
        $orderId = $this->_checkoutSession->getLastRealOrderId();
        return $this->_salesFactory->loadByIncrementId($orderId);
    }

    public function getEmail($order)
    {
        $email = $this->_orderDataHelper->getEmail($order);

        if (!(empty($email)))
            return $email;
        
        $order = $this->getLastRealOrder();

        return $this->_orderDataHelper->getEmail($order);
    }

    public function checkInstallationKey()
    {
        $key = trim($this->_helper->getGeneralConfigValue('key'));
       
        if (empty($key))
            $this->addNotification();
    }

    public function addNotification()
    {
        if (empty($this->_notifications->getLatestMissingKeyNotification()))
            $this->_notifications->createMissingKeyNotification();
    }
}
