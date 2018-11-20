<?php

namespace Trustpilot\Reviews\Helper;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Store\Model\StoreManagerInterface;

class OrderData extends AbstractHelper
{
    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->_storeManager = $storeManager;
    }

    public function getName($order)
    {
        if ($order->getCustomerIsGuest() == 1) {
            return $order->getBillingAddress()->getFirstName() . ' ' . $order->getBillingAddress()->getLastName();
        } else {
            return $order->getCustomerName();
        }
    }

    public function getEmail($order)
    {
        if ($this->is_empty($order))
            return '';

        try {
            if (!($this->is_empty($order->getCustomerEmail())))
                return $order->getCustomerEmail();
        } catch(\Exception $e) {
            // Just going to the next check
        }

        try {
            if (!($this->is_empty($order->getShippingAddress()->getEmail())))
                return $order->getShippingAddress()->getEmail();
        } catch (\Exception $e) {
            // Just going to the next check
        }

        try {
            if (!($this->is_empty($order->getBillingAddress()->getEmail())))
                return $order->getBillingAddress()->getEmail();
        } catch (\Exception $e) {
            // Just going to the next check
        }

        try {
            if (!($this->is_empty($order->getCustomerId())))
                return $this->_customer->load($order->getCustomerId())->getEmail();
        } catch (\Exception $e) {
            // Just skipping an email
        }

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
            foreach ($order->getAllVisibleItems() as $item) {
                $product = $item->getProduct();
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
        } catch (\Exception $e) {
            // Just skipping products data if we are not able to collect it
        }
        return $products;
    }
}
