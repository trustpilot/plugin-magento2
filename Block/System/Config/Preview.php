<?php
namespace Trustpilot\Reviews\Block\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Trustpilot\Reviews\Helper\Data;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Store\Model\ScopeInterface;

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
            $scope = $this->_helper->getScope();
            $storeId = $this->_helper->getWebsiteOrStoreId();
            switch ($scope) {
                case ScopeInterface::SCOPE_STORE:
                    break;
                case ScopeInterface::SCOPE_WEBSITE:
                    $storeId = $this->_storeManager->getWebsite($storeId)->getDefaultGroup()->getDefaultStoreId();
                    break;
                case ScopeInterface::SCOPE_TYPE_DEFAULT:
                    break;
            }
            $storeCode = $this->_storeManager->getStore($storeId)->getCode();

            $value = (isset($page) && !empty($page)) ? $page : $this->_helper->getTrustBoxConfigValue('trustbox_page');
            switch ($value) {
                case 'trustpilot_trustbox_homepage':
                    return $this->_storeManager->getStore($storeId)->getBaseUrl().'?___store='.$storeCode;
                case 'trustpilot_trustbox_category':
                    $collection = $this->_categoryCollectionFactory->create();
                    $collection->addAttributeToSelect('*');
                    $collection->setStore($storeId);
                    $collection->addAttributeToFilter('is_active', 1);
                    $collection->addAttributeToFilter('children_count', 0);
                    $category = $collection->getFirstItem();
                    $productUrl = strtok($category->getUrl(),'?').'?___store='.$storeCode;
                    return $productUrl;
                case 'trustpilot_trustbox_product':
                    $collection = $this->_productCollectionFactory->create();
                    $collection->addAttributeToSelect('*');
                    $collection->setStore($storeId);
                    $collection->addAttributeToFilter('status', 1);
                    $collection->addAttributeToFilter('visibility', array(2, 3, 4));
                    $product = $collection->getFirstItem();
                    $productUrl = strtok($product->setStoreId($storeId)->getUrlInStore(),'?').'?___store='.$storeCode;
                    return $productUrl;
                default:
                    return $this->_storeManager->getStore($storeId)->getBaseUrl();
            }
        } catch (\Exception $e) {
            if (empty($value)) {
                $this->_logger->error('Error: ' . $e->getMessage());
            } else {
                $this->_logger->error('Unable to find URL for a page ' . $value . '. Error: ' . $e->getMessage());
            }
            return $this->_storeManager->getStore()->getBaseUrl();
        }
    }
}
