<?php

namespace Trustpilot\Reviews\Helper;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\ConfigurableProduct\Api\LinkManagementInterface;
use Magento\Backend\Helper\Data as BackendHelper;
use Trustpilot\Reviews\Helper\Data;

class Products extends AbstractHelper
{
    private $_product;
    private $_helper;
    private $_linkManagement;
    private $_backendHelper;
    
    public function __construct(
        Product $product,
        Data $helper,
        LinkManagementInterface $linkManagement,
        BackendHelper $backendHelper)
    {
        $this->_product = $product;
        $this->_helper = $helper;
        $this->_linkManagement = $linkManagement;
        $this->_backendHelper = $backendHelper;
    }

    public function checkSkus() {
        $data = array();
        $page_id = 1;
        $productCollection = $this->_product
            ->getCollection()
            ->addAttributeToSelect(array('name'))
            // ->addAttributeToFilter('type_id', 'configurable')
            ->setPageSize(20);
        $lastPage = $productCollection->getLastPageNumber();
        $settings = json_decode($this->_helper->getConfig('master_settings_field'));
        $skuSelector = empty($settings->skuSelector) || $settings->skuSelector == 'none' ? 'sku' : $settings->skuSelector;
        while ($page_id < $lastPage) {
            set_time_limit(30);
            $collection = $productCollection->setCurPage($page_id)->load();
            if (isset($collection)) {
                foreach ($collection as $product) {
                    $sku = $this->_helper->loadSelector($product, $skuSelector);
                    // TODO: decide do we want to group products
                    // $childProducts = array();
                    // if ($product->getTypeId() == 'configurable') {
                    //     $simpleProductCollection = $this->_linkManagement->getChildren($product->getSku());
                    //     foreach ($simpleProductCollection as $childProduct) {
                    //         $productSku = $this->_helper->loadSelector($childProduct, $skuSelector);
                    //         if (empty($productSku)) {
                    //             $childItem = array();
                    //             $childItem['id'] = $childProduct->getId();
                    //             $childItem['name'] = $childProduct->getName();
                    //             $childItem['type'] = $childProduct->getTypeId();
                    //             $childItem['productAdminUrl'] = $this->_backendHelper->getUrl('catalog/product/edit', array('id' => $childProduct->getId()));
                    //             $childItem['productFrontendUrl'] = $childProduct->getProductUrl();
                    //             array_push($childProducts, $childItem);
                    //         }
                    //     }
                    // }
                    
                    if (empty($sku)) {
                        $item = array();
                        $item['id'] = $product->getId();
                        $item['name'] = $product->getName();
                        $item['type'] = $product->getTypeId();
                        $item['productAdminUrl'] = $this->_backendHelper->getUrl('catalog/product/edit', array('id' => $product->getId()));
                        $item['productFrontendUrl'] = $product->getProductUrl();
                        // $item['childProducts'] = $childProducts;
                        array_push($data, $item);
                    }
                }
            }
            $collection->clear();
            $page_id = $page_id + 1;
        }
        return $data;
    }
}