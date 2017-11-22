<?php
namespace Trustpilot\Invitation\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;
use Magento\Checkout\Model\Session;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\App\ProductMetadataInterface;

class Success extends Template
{
    protected $_salesFactory;

    protected $_checkoutSession;

    protected $_product;

    protected $_version;

    protected $_productMetadata;

    public function __construct(
        Context $context,
        Order $salesOrderFactory,
        Session $checkoutSession,
        Product $product,
        ProductMetadataInterface $productMetadata,
        array $data = []
    ) {
        $this->_salesFactory = $salesOrderFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_product = $product;
        $this->_productMetadata = $productMetadata;
        $this->_version = '1.0.8';
        parent::__construct($context, $data);
    }

    public function renderScript()
    {
        $data = $this->getOrder();
        return '
        <script type="text/javascript">
            document.addEventListener(\'DOMContentLoaded\', function() {
                tp(\'createInvitation\', JSON.parse(\''.json_encode($data, JSON_HEX_APOS).'\'));
            });
        </script>';
    }

    private function getOrder()
    {
        $orderId = $this->_checkoutSession->getLastOrderId();
        $order   = $this->_salesFactory->load($orderId);
        $items = $order->getAllItems();
        $products = array();
        
        foreach ($items as $i) {
            $product = $this->_product->load($i->getProductId());
            array_push(
                $products,
                array(
                    'productUrl' => $product->getProductUrl(),
                    'name' => $product->getName(),
                    //'brand' => $brand
                    'sku' => $product->getSku(),
                    //'gtin' => $gtin,
                    //'mpn' => $mpn,
                    'imageUrl' => $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage()
                ));
        }

        return $data = array(
            'recipientEmail' => $order->getCustomerEmail(),
            'recipientName' => $order->getCustomerName(),
            'referenceId' => $order->getRealOrderId(),
            'productSkus' => $this->getSkus($products),
            'source' => 'Magento-'.$this->_productMetadata->getVersion(),
            'pluginVersion' => $this->_version,
            'products' => $products,
          );
    }

    private function getSkus($products)
    {
        $skus = array();
        foreach ($products as $product) {
            array_push($skus, $product['sku']);
        }
        return $skus;
    }
}