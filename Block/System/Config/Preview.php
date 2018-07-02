<?php
namespace Trustpilot\Reviews\Block\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Trustpilot\Reviews\Helper\Data;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

class Preview extends Field
{
    protected $_helper;
    protected $_storeManager;
    protected $_categoryCollectionFactory;
    protected $_productCollectionFactory;
    protected $_template = 'system/config/preview.phtml';
    protected $_previewUrl;

    public function __construct(
        Context $context,
        Data $helper,
        CategoryCollectionFactory $categoryCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        array $data = [])
    {
        $this->_helper                      = $helper;
        $this->_storeManager                = $context->getStoreManager();
        $this->_categoryCollectionFactory   = $categoryCollectionFactory;
        $this->_productCollectionFactory    = $productCollectionFactory;
        $this->_previewUrl                  = $this->_helper->getGeneralConfigValue('PreviewUrl');
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    public function getXPathValue()
    {
        return $this->_helper->getTrustboxConfigValue('trustbox_xpath');
    }

    public function getIframeCssUrl()
    {
        return $this->getViewFileUrl('Trustpilot_Reviews::css/trustpilot-iframe.css');
    }

    public function getPreviewUrl()
    {
        return $this->_previewUrl;
    }

    public function getPageUrl($page)
    {
        try {
            $storeId = $this->_helper->getWebsiteOrStoreId();
            $value = (isset($page) && !empty($page)) ? $page : $this->_helper->getTrustBoxConfigValue('trustbox_page');        
            switch ($value) {
                    case 'trustpilot_trustbox_homepage':
                        return $this->_storeManager->getStore($storeId)->getUrl();
                    case 'trustpilot_trustbox_category':
                        $collection = $this->_categoryCollectionFactory->create();
                        $collection->addAttributeToSelect('*');
                        $collection->setStore($storeId);
                        $collection->addAttributeToFilter('is_active', 1);
                        $collection->addAttributeToFilter('children_count', 0);
                        $category = $collection->getFirstItem();
                        $storeCode = $this->_storeManager->getStore($storeId)->getCode();
                        $productUrl = strtok($category->getUrl(),'?').'?___store='.$storeCode;
                        return $productUrl;
                    case 'trustpilot_trustbox_product':
                        $collection = $this->_productCollectionFactory->create();
                        $collection->addAttributeToSelect('*');
                        $collection->setStore($storeId);
                        $collection->addAttributeToFilter('status', 1);
                        $collection->addAttributeToFilter('visibility', array(2, 3, 4));
                        $product = $collection->getFirstItem();
                        $storeCode = $this->_storeManager->getStore($storeId)->getCode();
                        $productUrl = strtok($product->setStoreId($storeId)->getUrlInStore(),'?').'?___store='.$storeCode;
                        return $productUrl;
                    default:
                        return $this->_storeManager->getStore($storeId)->getUrl();
            }
        } catch (\Exception $e) {
            if (empty($value)) {
                $this->_logger->debug('Error: ' . $e->getMessage());
            } else {
                $this->_logger->debug('Unable to find URL for a page ' . $value . '. Error: ' . $e->getMessage());
            }
            return $this->_storeManager->getStore()->getBaseUrl();
        }
    }
}
