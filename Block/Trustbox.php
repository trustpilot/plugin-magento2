<?php
namespace Trustpilot\Reviews\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Registry;
use Trustpilot\Reviews\Helper\Data;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ObjectManager;
use Magento\ConfigurableProduct\Api\LinkManagementInterface;

class Trustbox extends Template
{
    protected $_helper;
    protected $_tbWidgetScriptUrl;
    protected $_registry;
    protected $_request;
    protected $_storeManager;
    protected $_urlInterface;
    protected $_linkManagement;

    public function __construct(
        Context $context,
        Data $helper,
        Registry $registry,
        Http $request,
        LinkManagementInterface $linkManagement,
        array $data = [])
    {
        $this->_helper = $helper;
        $this->_registry = $registry;
        $this->_request = $request;
        $this->_storeManager = $context->getStoreManager();
        $this->_tbWidgetScriptUrl = \Trustpilot\Reviews\Model\Config::TRUSTPILOT_WIDGET_SCRIPT_URL;
        $this->_urlInterface = ObjectManager::getInstance()->get('Magento\Framework\UrlInterface');
        $this->_linkManagement = $linkManagement;
        parent::__construct($context, $data);
    }

    private function getCurrentUrl() {
        return $this->_urlInterface->getCurrentUrl();
    }

    public function getWidgetScriptUrl()
    {
        return $this->_tbWidgetScriptUrl;
    }

    public function loadTrustboxes()
    {
        $scope = $this->_helper->getScope();
        $storeId = $this->_helper->getWebsiteOrStoreId();
        $settings = json_decode($this->_helper->getConfig('master_settings_field', $storeId, $scope));
        $trustboxSettings = $settings->trustbox;
        if (isset($trustboxSettings->trustboxes)) {
            $currentUrl = $this->getCurrentUrl();
            $currentCategory = $this->_registry->registry('current_category');
            $loadedTrustboxes = $this->loadPageTrustboxes($settings, $currentUrl);

            if ($this->_registry->registry('current_product')) {
                $loadedTrustboxes = array_merge((array)$this->loadPageTrustboxes($settings, 'product'), (array)$loadedTrustboxes);
            } else if ($currentCategory) {
                $loadedTrustboxes = array_merge((array)$this->loadPageTrustboxes($settings, 'category'), (array)$loadedTrustboxes);
                if ($this->repeatData($loadedTrustboxes)) {
                    $trustboxSettings->categoryProductsData = $this->loadCategoryProductInfo($scope, $storeId, $currentCategory);
                }
            }
            if ($this->_request->getFullActionName() == 'cms_index_index') {
                $loadedTrustboxes = array_merge((array)$this->loadPageTrustboxes($settings, 'landing'), (array)$loadedTrustboxes);
            }

            if (count($loadedTrustboxes) > 0) {
                $trustboxSettings->trustboxes = $loadedTrustboxes;
                return json_encode($trustboxSettings, JSON_HEX_APOS);
            }
        }

        return '{"trustboxes":[]}';
    }

    private function repeatData($trustBoxes) {
        foreach ($trustBoxes as $trustbox) {
            if (isset($trustbox->repeat) && $trustbox->repeat || true) {
                return true;
            }
        }
        return false;
    }

    private function loadSkus($current_product, $skuSelector, $includeIds) 
    {
        $skus = array();
        if ($includeIds) {
            array_push($skus, \Trustpilot\Reviews\Model\Config::TRUSTPILOT_PRODUCT_ID_PREFIX . $current_product->getId());
        }
        $productSku = $this->_helper->loadSelector($current_product, $skuSelector);
        if ($productSku) {
            array_push($skus, $productSku);
        }

        if ($current_product->getTypeId() == 'configurable') {
            $collection = $this->_linkManagement->getChildren($current_product->getSku());
            foreach ($collection as $product) {
                if ($includeIds) {
                    array_push($skus, \Trustpilot\Reviews\Model\Config::TRUSTPILOT_PRODUCT_ID_PREFIX . $product->getId());
                }
                $productSku = $this->_helper->loadSelector($product, $skuSelector);
                if ($productSku) {
                    array_push($skus, $productSku);
                }
            }
        }
        return implode(',', $skus);
    }

    private function loadPageTrustboxes($settings, $page)
    {
        $data = [];
        $skuSelector = empty($settings->skuSelector) || $settings->skuSelector == 'none' ? 'sku' : $settings->skuSelector;
        foreach ($settings->trustbox->trustboxes as $trustbox) {
            if ((rtrim($trustbox->page, '/') == rtrim($page, '/') || $this->checkCustomPage($trustbox->page, $page)) && $trustbox->enabled == 'enabled') {
                $current_product = $this->_registry->registry('current_product');
                if ($current_product) {
                    $sku = $this->loadSkus($current_product, $skuSelector, true);
                    if (strlen($sku) > \Trustpilot\Reviews\Model\Config::MAX_SKU_LENGTH) {
                        $sku = $this->loadSkus($current_product, $skuSelector, false);
                    }
                    $trustbox->sku = $sku;
                    $trustbox->name = $current_product->getName();
                }
                array_push($data, $trustbox);
            }
        }
        return $data;
    }

    private function checkCustomPage($tbPage, $page) {
        return (
            $tbPage == strtolower(base64_encode($page . '/')) ||
            $tbPage == strtolower(base64_encode($page)) ||
            $tbPage == strtolower(base64_encode(rtrim($page, '/')))
        );
    }

    public function loadCategoryProductInfo($scope, $storeId, $category = null) {
        try {
            if ($category == null) {
                $block = $this->getLayout()->getBlock('category.products.list');
                $products = $block->getLoadedProductCollection();
            } else {
                $products = $category->getProductCollection();
            }
            return $this->_helper->loadCategoryProductInfo($products, $scope, $storeId);
        } catch(\Throwable $e) {
            return array();
        } catch(\Exception $e) {
            return array();
        }
    }
}
