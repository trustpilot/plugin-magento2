<?php
namespace Trustpilot\Reviews\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;
use Magento\Checkout\Model\Session;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\App\ProductMetadataInterface;
use Trustpilot\Reviews\Helper\Data;
use Trustpilot\Reviews\Helper\Notifications;
use Magento\Customer\Model\Customer;

class Success extends Template
{
    protected $_salesFactory;

    protected $_checkoutSession;

    protected $_product;

    protected $_version;

    protected $_productMetadata;

    protected $_helper;

    protected $_notifications;

    protected $_customer;

    public function __construct(
        Context $context,
        Order $salesOrderFactory,
        Session $checkoutSession,
        Product $product,
        ProductMetadataInterface $productMetadata,
        Data $helper,
        Notifications $notifications,
        Customer $customer,
        array $data = []
    ) {
        $this->_salesFactory = $salesOrderFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_product = $product;
        $this->_productMetadata = $productMetadata;
        $this->_helper = $helper;
        $this->_notifications = $notifications;
        $this->_customer = $customer;
        $this->_version = '#{Octopus.Release.Number}';
        parent::__construct($context, $data);
    }

    public function getOrder()
    {
        $orderId = $this->_checkoutSession->getLastOrderId();
        $order   = $this->_salesFactory->load($orderId);
        $items = $order->getAllItems();
        $products = [];
        
        foreach ($items as $i) {
            $product = $this->_product->load($i->getProductId());
            $brand = $product->getAttributeText('manufacturer');
            array_push(
                $products,
                [
                    'productUrl' => $product->getProductUrl(),
                    'name' => $product->getName(),
                    'brand' => $brand ? $brand : '',
                    'sku' => $product->getSku(),
                    'imageUrl' => $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage()
                ]
            );
        }

        $data = [
            'recipientEmail' => trim($this->getEmail($order)),
            'recipientName' => $order->getCustomerName(),
            'referenceId' => $order->getRealOrderId(),
            'productSkus' => $this->getSkus($products),
            'source' => 'Magento-'.$this->_productMetadata->getVersion(),
            'pluginVersion' => $this->_version,
            'products' => $products,
        ];

        return json_encode($data, JSON_HEX_APOS);
    }

    public function getSkus($products)
    {
        $skus = [];
        foreach ($products as $product) {
            array_push($skus, $product['sku']);
        }
        return $skus;
    }

    public function getLastRealOrder()
    {
        $orderId = $this->_checkoutSession->getLastRealOrderId();
        return $this->_salesFactory->loadByIncrementId($orderId);
    }

    public function getEmail($order)
    {
        $email = $this->tryGetEmail($order);

        if (!($this->is_empty($email)))
            return $email;
        
        $order = $this->getLastRealOrder();

        return $this->tryGetEmail($order);
    }

    public function tryGetEmail($order)
    {
        if ($this->is_empty($order))
            return '';
      
        if (!($this->is_empty($order->getCustomerEmail())))
            return $order->getCustomerEmail();

        else if (!($this->is_empty($order->getShippingAddress()->getEmail())))
            return $order->getShippingAddress()->getEmail();

        else if (!($this->is_empty($order->getBillingAddress()->getEmail())))
            return $order->getBillingAddress()->getEmail();

        else if (!($this->is_empty($order->getCustomerId())))
            return $this->_customer->load($order->getCustomerId())->getEmail();
       
        return '';
    }

    public function checkInstallationKey()
    {
        $key = trim($this->_helper->getConfigValue('key'));
       
        if ($this->is_empty($key))
            $this->addNotification();
    }

    public function addNotification()
    {
        if ($this->is_empty($this->_notifications->getLatestMissingKeyNotification()))
            $this->_notifications->createMissingKeyNotification();
    }

    public function is_empty($var)
    { 
        return empty($var);
    }
}
