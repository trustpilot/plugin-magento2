<?php
namespace Trustpilot\Reviews\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template;
use Trustpilot\Reviews\Helper\Data;
use Trustpilot\Reviews\Helper\OrderData;

class Success extends Template
{
    protected $_salesFactory;
    protected $_checkoutSession;
    protected $_helper;
    protected $_orderData;

    public function __construct(
        Context $context,
        Order $salesOrderFactory,
        Session $checkoutSession,
        Data $helper,
        OrderData $orderData,
        array $data = [])
    {
        $this->_salesFactory = $salesOrderFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_helper = $helper;
        $this->_orderData = $orderData;

        parent::__construct($context, $data);
    }

    public function getOrder()
    {
        try {
            $orderId = $this->_checkoutSession->getLastOrderId();
            $order   = $this->_salesFactory->load($orderId);
            $storeId = $order->getStoreId();
            
            $general_settings = json_decode($this->_helper->getConfig('master_settings_field', $storeId))->general;
            $data = $this->_orderData->getInvitation($order, 'magento2_success', \Trustpilot\Reviews\Model\Config::WITH_PRODUCT_DATA);

            if (!in_array('trustpilotOrderConfirmed', $general_settings->mappedInvitationTrigger)) {
                $data['payloadType'] = 'OrderStatusUpdate';
            }

            return json_encode($data, JSON_HEX_APOS);
        } catch (\Exception $e) {
            $error = array('message' => $e->getMessage());
            $data = array('error' => $error);
            return json_encode($data, JSON_HEX_APOS);
        }            
    }
}
