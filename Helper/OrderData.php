<?php

namespace Trustpilot\Reviews\Helper;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Store\Model\StoreManagerInterface;
use Trustpilot\Reviews\Helper\Data;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

class OrderData extends AbstractHelper
{
    protected $_storeManager;
    protected $_helper;
    protected $_categoryCollectionFactory;

    public function __construct(
        StoreManagerInterface $storeManager,
        Data $helper,
        CategoryCollectionFactory $categoryCollectionFactory)
    {
        $this->_storeManager = $storeManager;
        $this->_helper = $helper;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
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
                $invitation['templateParams'] = array((string)$this->getWebsiteId($order), (string)$this->getGroupId($order), (string)$order->getStoreId());
            } catch (\Exception $ex) {}

            if ($collect_product_data == \Trustpilot\Reviews\Model\Config::WITH_PRODUCT_DATA) {
                $products = $this->getProducts($order);
                $invitation['products'] = $products;
                $invitation['productSkus'] = $this->getSkus($products);
            }
        }
        return $invitation;
    }

    public function getWebsiteId($order) {
        return  $this->_storeManager->getStore($order->getStoreId())->getWebsite()->getId();
    }

    public function getGroupId($order) {
        return  $this->_storeManager->getStore($order->getStoreId())->getGroupId();
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
            $settings = json_decode($this->_helper->getConfig('master_settings_field', $order->getStoreId()));
            $skuSelector = $settings->skuSelector;
            $gtinSelector = $settings->gtinSelector;
            $mpnSelector = $settings->mpnSelector;

            $items = $order->getAllVisibleItems();
            foreach ($items as $item) {
                $product = $item->getProduct();

                $childProducts = array();
                if ($item->getHasChildren()) {
                    $orderChildItems = $item->getChildrenItems();
                    foreach ($orderChildItems as $cpItem) {
                        array_push($childProducts, $cpItem->getProduct());
                    }
                }
    
                $manufacturer = $this->_helper->loadSelector($product, 'manufacturer', $childProducts);
                $sku = $this->_helper->loadSelector($product, $skuSelector, $childProducts);
                $mpn = $this->_helper->loadSelector($product, $mpnSelector, $childProducts);
                $gtin = $this->_helper->loadSelector($product, $gtinSelector, $childProducts);
                array_push(
                    $products,
                    array(
                        'price' => $product->getFinalPrice(),
                        'currency' => $order->getOrderCurrencyCode(),
                        'description' => strip_tags($product->getDescription()),
                        'meta' => array(
                            'title' => $product->getMetaTitle() ? $product->getMetaTitle() : $product->getName(),
                            'keywords' => $product->getMetaKeyword() ? $product->getMetaKeyword() : $product->getName(),
                            'description' => $product->getMetaDescription() ? $product->getMetaDescription() : substr(strip_tags($product->getDescription()), 0, 255),
                        ),
                        'manufacturer' => $manufacturer ? $manufacturer : '',
                        'categories' => $this->getProductCategories($product, $childProducts),
                        'images' => $this->getAllImages($product, $childProducts),
                        'videos' => $this->getAllVideos($product, $childProducts),
                        'tags' => null,
                        'productUrl' => $product->getProductUrl(),
                        'name' => $product->getName(),
                        'brand' => $product->getBrand() ? $product->getBrand() : $manufacturer,
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

    function getProductCategories($product, $childProducts = null) {
        $categories = array();
        $categoryIds = array();

        if (!empty($childProducts)) {
            foreach ($childProducts as $childProduct) {
                $childCategoryIds = $childProduct->getCategoryIds();
                if (!empty($childCategoryIds)) {
                    $categoryIds = array_merge($categoryIds, $childCategoryIds);
                }
            }
        } else {
            $categoryIds = $product->getCategoryIds();
        }

        $catCollection = $this->_categoryCollectionFactory->create();
        $catCollection
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('entity_id', $categoryIds);

        foreach ($catCollection as $category) {
            array_push($categories, $category->getName());
        }

        return $categories;
    }
    
    function getAllImages($product, $childProducts = null) {
        $images = array();

        if (!empty($childProducts)) {
            foreach ($childProducts as $childProduct) {
                foreach ($childProduct->getMediaGalleryImages() as $image) {
                    array_push($images, $image->getUrl());
                }
            }
        }
        
        foreach ($product->getMediaGalleryImages() as $image) {
            array_push($images, $image->getUrl());
        }

        return $images;
    }

    function getAllVideos($product, $childProducts = null) {
        $videos = array();

        if (!empty($childProducts)) {
            foreach ($childProducts as $childProduct) {
                foreach ($childProduct->getMediaGalleryImages() as $image) {
                    $imageData = $image->getData();
                    if (isset($imageData['media_type']) && $imageData['media_type'] == 'external-video') {
                        array_push($videos, $imageData['video_url']);
                    }
                    
                }
            }
        }
        
        foreach ($product->getMediaGalleryImages() as $image) {
            $imageData = $image->getData();
            if (isset($imageData['media_type']) && $imageData['media_type'] == 'external-video') {
                array_push($videos, $imageData['video_url']);
            }
        }

        return $videos;
    }
}
