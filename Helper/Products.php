<?php

namespace Trustpilot\Reviews\Helper;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\ConfigurableProduct\Api\LinkManagementInterface;
use Magento\Backend\Helper\Data as BackendHelper;

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

    public function checkSkus($skuSelector) {
        $data = array();
        $page_id = 1;
        $productCollection = $this->_product
            ->getCollection()
            ->addAttributeToSelect(array('name', $skuSelector))
            ->setPageSize(20);
        $lastPage = $productCollection->getLastPageNumber();
        while ($page_id <= $lastPage) {
            set_time_limit(30);
            $collection = $productCollection->setCurPage($page_id)->load();
            if (isset($collection)) {
                foreach ($collection as $product) {
                    $sku = $this->_helper->loadSelector($product, $skuSelector);
                    
                    if (empty($sku)) {
                        $item = array();
                        $item['id'] = $product->getId();
                        $item['name'] = $product->getName();
                        $item['productAdminUrl'] = $this->_backendHelper->getUrl('catalog/product/edit', array('id' => $product->getId()));
                        $item['productFrontendUrl'] = $product->getProductUrl();
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
