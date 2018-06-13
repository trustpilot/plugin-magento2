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

    public function getPageUrl($page)
    {
        $value = (isset($page) && !empty($page)) ? $page : $this->_helper->getTrustBoxConfigValue('trustbox_page');
        switch ($value) {
                case 'trustpilot_trustbox_homepage':
                    return $this->_storeManager->getStore()->getBaseUrl();
                case 'trustpilot_trustbox_category':
                    $collection = $this->_categoryCollectionFactory->create();
                    $collection->addAttributeToSelect('*');
                    $collection->addAttributeToFilter('is_active', 1);
                    $collection->addAttributeToFilter('children_count', 0);
                    $category = $collection->getFirstItem();
                    return $category->getUrl();
                case 'trustpilot_trustbox_product':
                    $collection = $this->_productCollectionFactory->create();
                    $collection->addAttributeToSelect('*');
                    $collection->addAttributeToFilter('status', 1);
                    $collection->addAttributeToFilter('visibility', array(2, 3, 4));
                    $product = $collection->getFirstItem();
                    return $product->getProductUrl();
                default:
                    return $this->_storeManager->getStore()->getBaseUrl();
        }
    }
}
