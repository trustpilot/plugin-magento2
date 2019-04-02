<?php

namespace Trustpilot\Reviews\Helper;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Store\Model\StoreManagerInterface;
use Trustpilot\Reviews\Helper\Data;

class OrderData extends AbstractHelper
{
    protected $_storeManager;
    protected $_helper;

    public function __construct(
        StoreManagerInterface $storeManager,
        Data $helper)
    {
        $this->_storeManager = $storeManager;
        $this->_helper = $helper;
    }

    public function getInvitation($order, $hook, $collect_product_data = \Trustpilot\Reviews\Model\Config::WITH_PRODUCT_DATA) 
    {
        $invitation = null;
        if (!is_null($order)) {
            $invitation = array();
            $invitation['recipientEmail'] = trim($this->getEmail($order));
            $invitation['recipientName'] = trim($this->getName($order));
            $invitation['referenceId'] = $order->getRealOrderId();
            $invitation['source'] = 'Magento-' . $this->_helper->getVersion();
            $invitation['pluginVersion'] = \Trustpilot\Reviews\Model\Config::TRUSTPILOT_PLUGIN_VERSION;
            $invitation['hook'] = $hook;
            $invitation['orderStatusId'] = $order->getState();
            $invitation['orderStatusName'] = $order->getStatusLabel();
            try {
                $invitation['totalCost'] = $order->getGrandTotal();
                $invitation['currency'] = $order->getOrderCurrencyCode();
            } catch (\Exception $ex) {}
            if ($collect_product_data == \Trustpilot\Reviews\Model\Config::WITH_PRODUCT_DATA) {
                $products = $this->getProducts($order);
                $invitation['products'] = $products;
                $invitation['productSkus'] = $this->getSkus($products);
            }
        }
        return $invitation;
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
        $skus = array();
        foreach ($products as $product) {
            array_push($skus, $product['sku']);
        }
        return $skus;
    }

    public function is_empty($var)
    { 
        return empty($var);
    }
    
    public function getProducts($order)
    {
        $products = array();
        try {
            $settings = json_decode($this->_helper->getConfig('master_settings_field'));
            $skuSelector = $settings->skuSelector;
            $gtinSelector = $settings->gtinSelector;
            $mpnSelector = $settings->mpnSelector;
        
            $items = $order->getAllVisibleItems();
            foreach ($items as $i) {
                $product = $i->getProduct();
                $manufacturer = $this->_helper->loadSelector($product, 'manufacturer');
                $sku = $this->_helper->loadSelector($product, $skuSelector);
                $mpn = $this->_helper->loadSelector($product, $mpnSelector);
                $gtin = $this->_helper->loadSelector($product, $gtinSelector);
                array_push(
                    $products,
                    array(
                        'productUrl' => $product->getProductUrl(),
                        'name' => $product->getName(),
                        'brand' => $manufacturer ? $manufacturer : '',
                        'sku' => $sku ? $sku : '',
                        'mpn' => $mpn ? $mpn : '',
                        'gtin' => $gtin ? $gtin : '',
                        'imageUrl' => $this->_storeManager->getStore($order->getStoreId())->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
                            . 'catalog/product' . $product->getImage()
                    )
                );
            }
        } catch (\Exception $e) {
            // Just skipping products data if we are not able to collect it
        }

        return $products;
    }
}
