<?php

namespace Trustpilot\Reviews\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Store\Model\ScopeInterface as StoreScopeInterface;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\App\Cache\TypeListInterface;
use \Magento\Framework\App\Cache\Frontend\Pool;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use \Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigCollectionFactory;
use \Magento\Store\Model\StoreRepository;

class Data extends AbstractHelper
{
    const XML_PATH_TRUSTPILOT_GENERAL = 'trustpilotGeneral/general/';
    const XML_PATH_TRUSTPILOT_TRUSTBOX = 'trustpilotTrustbox/trustbox/';
    const TRUSTPILOT_SETTINGS = 'trustpilot/trustpilot_general_group/';

    protected $_request;
    protected $_storeManager;
    protected $_categoryCollectionFactory;
    protected $_productCollectionFactory;
    protected $_configWriter;
    protected $_cacheTypeList;
    protected $_cacheFrontendPool;
    protected $_attributeFactory;
    protected $_searchCriteriaBuilder;
    protected $_attributeRepository;
    protected $_configCollectionFactory;
    protected $_logger;
    protected $_httpClient;
    protected $_storeRepository;
    protected $_integrationAppUrl;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        CategoryCollectionFactory $categoryCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        Pool $cacheFrontendPool,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AttributeRepositoryInterface $attributeRepository,
        ConfigCollectionFactory $configCollectionFactory,
        TrustpilotHttpClient $httpClient,
        StoreRepository $storeRepository
    ) {
        $this->_storeManager = $storeManager;
        $this->_categoryCollectionFactory   = $categoryCollectionFactory;
        $this->_productCollectionFactory    = $productCollectionFactory;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_attributeRepository = $attributeRepository;
        $this->_configWriter = $configWriter;
        parent::__construct($context);
        $this->_request = $context->getRequest();
        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheFrontendPool = $cacheFrontendPool;
        $this->_configCollectionFactory = $configCollectionFactory;
        $this->_logger = $context->getLogger();
        $this->_httpClient = $httpClient;
        $this->_storeRepository = $storeRepository;
        $this->_integrationAppUrl = \Trustpilot\Reviews\Model\Config::TRUSTPILOT_INTEGRATION_APP_URL;
    }

    public function getIntegrationAppUrl()
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
            || isset($_SERVER['HTTP_USESSL'])
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
            || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on')
            ? "https:" : "http:";
        $domainName = $protocol . $this->_integrationAppUrl;
        return $domainName;
    }

    public function getKey($storeId = null)
    {
        return trim(json_decode(self::getConfig('master_settings_field', $storeId))->general->key);
    }

    private function getDefaultConfigValues($key)
    {
        $config = array();
        $config['master_settings_field'] = json_encode(
            array(
                'general' => array(
                    'key' => '',
                    'invitationTrigger' => 'orderConfirmed',
                    'mappedInvitationTrigger' => array(),
                ),
                'trustbox' => array(
                    'trustboxes' => array(),
                ),
                'skuSelector' => 'none',
                'mpnSelector' => 'none',
                'gtinSelector' => 'none',
                'pastOrderStatuses' => array('processing', 'complete'),
            )
        );
        $config['sync_in_progress'] = 'false';
        $config['show_past_orders_initial'] = 'true';
        $config['past_orders'] = '0';
        $config['failed_orders'] = '{}';
        $config['custom_trustboxes'] = '{}';

        if (isset($config[$key])) {
            return $config[$key];
        }
        return false;
    }

    public function getWebsiteOrStoreId()
    {
        if ($this->_request->getParam('store')) {
            return (int) $this->_request->getParam('store', 0);
        }
        if ($this->_request->getParam('website')) {
            return (int) $this->_request->getParam('website', 0);
        }
        if ($this->isAdminPage() && $this->_storeManager->getStore()->getWebsiteId()) {
            return (int) $this->_storeManager->getStore()->getStoreId();
        }
        if ($this->_storeManager->getStore()->getStoreId()) {
            return (int) $this->_storeManager->getStore()->getStoreId();
        }
        return 0;
    }

    public function getScope()
    {
        // user is on the admin store level
        if (strlen($this->_request->getParam('store'))) {
            return StoreScopeInterface::SCOPE_STORES;
        }
        // user is on the admin website level
        if (strlen($this->_request->getParam('website'))) {
            return StoreScopeInterface::SCOPE_WEBSITES;
        }
        // is user is on admin page, try to automatically detect his website scope
        if ($this->isAdminPage() && $this->_storeManager->getStore()->getWebsiteId()) {
            return StoreScopeInterface::SCOPE_WEBSITES;
        }
        // user is on the storefront
        if ($this->_storeManager->getStore()->getStoreId()) {
            return StoreScopeInterface::SCOPE_STORES;
        }
        // user at admin default level
        return 'default';
    }

    public function getConfig($config, $storeId = null)
    {
        $path = self::TRUSTPILOT_SETTINGS . $config;

        if ($this->getWebsiteOrStoreId()) {
            $collection = $this->_configCollectionFactory->create()
                ->addFieldToFilter('scope', $this->getScope())
                ->addFieldToFilter('path', $path)
                ->addFieldToFilter('scope_id', $this->getWebsiteOrStoreId());
            $data = $collection->getFirstItem()->getData();

            if ($data) {
                $setting = $data['value'];
            } else {
                $setting = $this->scopeConfig->getValue($path, $this->getScope(), $this->getWebsiteOrStoreId());
            }
        } else {
            $setting = $this->scopeConfig->getValue($path, StoreScopeInterface::SCOPE_STORES, $storeId);
        }

        return $setting ? $setting : $this->getDefaultConfigValues($config);
    }

    public function getVersion() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        if (method_exists($productMetadata, 'getVersion')) {
            return $productMetadata->getVersion();
        } else {
            return \Magento\Framework\AppInterface::VERSION;
        }
    }

    public function getPageUrls()
    {
        $pageUrls = new \stdClass();
        $pageUrls->landing = $this->getPageUrl('trustpilot_trustbox_homepage');
        $pageUrls->category = $this->getPageUrl('trustpilot_trustbox_category');
        $pageUrls->product = $this->getPageUrl('trustpilot_trustbox_product');
        $customPageUrls = json_decode($this->getConfig('page_urls'));
        $urls = (object) array_merge((array) $customPageUrls, (array) $pageUrls);
        return $urls;
    }

    public function getFirstProduct()
    {
        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->setStore($this->getWebsiteOrStoreId());
        $collection->addStoreFilter($this->getWebsiteOrStoreId());
        $collection->addAttributeToFilter('status', 1);
        $collection->addAttributeToFilter('visibility', array(2, 3, 4));
        return $collection->getFirstItem();
    }

    public function getPageUrl($page)
    {
        try {
            $storeId = $this->getWebsiteOrStoreId();
            $storeCode = $this->_storeManager->getStore($storeId)->getCode();
            switch ($page) {
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
                    $product = $this->getFirstProduct();
                    $productUrl = strtok($product->setStoreId($storeId)->getUrlInStore(),'?').'?___store='.$storeCode;
                    return $productUrl;
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

    public function getProductIdentificationOptions()
    {
        $fields = array('none', 'sku', 'id');
        $optionalFields = array('upc', 'isbn', 'brand', 'manufacturer');
        $dynamicFields = array('mpn', 'gtin');
        $attrs = array_map(function ($t) { return $t; }, $this->getAttributes());

        foreach ($attrs as $attr) {
            foreach ($optionalFields as $field) {
                if ($attr == $field) {
                    array_push($fields, $field);
                }
            }
            foreach ($dynamicFields as $field) {
                if (stripos($attr, $field) !== false) {
                    array_push($fields, $attr);
                }
            }
        }

        return json_encode($fields);
    }

    private function getAttributes()
    {
        $attr = array();

        $searchCriteria = $this->_searchCriteriaBuilder->create();
        $attributeRepository = $this->_attributeRepository->getList(
            'catalog_product',
            $searchCriteria
        );
        foreach ($attributeRepository->getItems() as $items) {
            array_push($attr, $items->getAttributeCode());
        }
        return $attr;
    }

    public function loadSelector($product, $selector, $childProducts = null)
    {
        switch ($selector) {
            case 'id':
                return (string) $product->getId();
            default:
                $values = array();
                if (!empty($childProducts)) {
                    foreach ($childProducts as $product) {
                        $value = $this->loadAttributeValue($product, $selector);
                        if (!empty($value)) {
                            array_push($values, $value);
                        }
                    }
                }
                if (!empty($values)) {
                    return implode(',', $values);
                } else {
                    return $this->loadAttributeValue($product, $selector);
                }
        }
    }

    private function loadAttributeValue($product, $selector)
    {
        try {
            if ($attribute = $product->getResource()->getAttribute($selector)) {
                $data = $product->getData($selector);
                $label = $attribute->getSource()->getOptionText($data);
                if (is_array($label)) {
                    $label = implode(', ', $label);
                }
                return $label ? $label : (string) $data;
            } else {
                return $label = '';
            }
        } catch(\Exception $e) {
            return '';
        }
    }

    public function setConfig($config, $value, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0)
    {
        if ($scope === 'store') {
            $scope = 'stores';
        } elseif ($scope === 'website') {
            $scope = 'websites';
        }
        $this->_configWriter->save(self::TRUSTPILOT_SETTINGS . $config,  $value, $scope, $scopeId);

        $this->_cacheTypeList->cleanType('block_html');
        foreach ($this->_cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    }

    public function log($message, $exception)
    {
        $this->_logger->error($message, ['exception' => $exception]);
        $log = array(
            'platform' => 'Magento2',
            'version'  => \Trustpilot\Reviews\Model\Config::TRUSTPILOT_PLUGIN_VERSION,
            'key'      => $this->getKey(),
            'message'  => $message,
        );
        $this->_httpClient->postLog($log, $this->getWebsiteOrStoreId());
    }

    public function getStoreInformation() {
        $stores = $this->_storeRepository->getList();
        $result = array();
        //Each store view is unique
        foreach ($stores as $store) {
            if ($store->isActive() && $store->getId() != 0) {
                $names = array(
                    'site'      => $store->getWebsite()->getName(),
                    'store'     => $store->getGroup()->getName(),
                    'view'      => $store->getName(),                    
                );
                $item = array(
                    'ids'       => array((string) $store->getWebsite()->getId(), (string) $store->getGroupId(), (string) $store->getStoreId()),
                    'names'     => $names,
                    'domain'    => parse_url($store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB), PHP_URL_HOST),
                );
                array_push($result, $item);
            }
        }
        return  base64_encode(json_encode($result));
    }

    public function isAdminPage() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $state =  $objectManager->get('Magento\Framework\App\State');
        return 'adminhtml' === $state->getAreaCode();
    }
}
