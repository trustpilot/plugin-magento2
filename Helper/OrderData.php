<?php

namespace Trustpilot\Reviews\Helper;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Store\Model\StoreManagerInterface;

class OrderData extends AbstractHelper
{
    protected $_product;

    public function __construct(
        Product $product,
        StoreManagerInterface $storeManager
    ) {
        $this->_storeManager = $storeManager;
        $this->_product = $product;
    }

    public function getEmail($order)
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
    
    public function getSkus($products)
    {
        $skus = [];
        foreach ($products as $product) {
            array_push($skus, $product['sku']);
        }
        return $skus;
    }

    public function is_empty($var)
    { 
        return empty($var);
    }
    
    public function getProducts($order){
        $products = [];
        try {
            $items = $order->getAllItems();
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
                        'imageUrl' => $this->_storeManager->getStore($order->getStoreId())->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) 
                            . 'catalog/product' . $product->getImage()
                    ]
                );
            }
        } catch (Exception $e) {
            // Just skipping products data if we are not able to collect it
        }
        return $products;
    }
}
