<?php
namespace Trustpilot\Reviews\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use \Psr\Log\LoggerInterface;
use Trustpilot\Reviews\Helper\Data;
use Trustpilot\Reviews\Helper\TrustpilotHttpClient;

class ConfigObserver implements ObserverInterface
{
    private $_helper;
    private $_logger;
    private $_trustpilotHttpClient;

    public function __construct(
        LoggerInterface $logger,
        TrustpilotHttpClient $trustpilotHttpClient,
        Data $helper)
    {
        $this->_logger = $logger;
        $this->_helper = $helper;
        $this->_trustpilotHttpClient = $trustpilotHttpClient;
    }

    public function execute(EventObserver $observer)
    {
        $key = $this->_helper->getGeneralConfigValue('key');
        $settings = self::getSettings();
        $this->_trustpilotHttpClient->postSettings($key, $settings);
    }

    public function getSettings() 
    {
        $globalSettings = new \stdClass();
        $globalSettings->source         = 'Magento2';
        $globalSettings->pluginVersion  = $this->_helper->getGeneralConfigValue('ReleaseNumber');
        $globalSettings->version = 'Magento-'.$this->_helper->getVersion();
        $stores = $this->getStores();
        $globalSettings->stores = array();
        foreach ($stores as $store) {
            $general = new \stdClass();
            $general->key            = trim($this->_helper->getGeneralConfigValue('key'));
            $general->storeId        = $store->getId();
            $general->storeCode      = $store->getCode();
            $general->storeName      = $store->getName();
            $general->storeTitle     = $store->getTitle();
            $general->storeActive    = $store->isActive();
            $general->storeHomeUrl   = $store->getCurrentUrl();
            $general->websiteId      = $store["website_id"];

            $trustbox = new \stdClass();
            $trustbox->enabled  = trim($this->_helper->getTrustboxConfigValueByStore('trustbox_enable', $store->getId()));
            $trustbox->snippet  = base64_encode(trim($this->_helper->getTrustboxConfigValueByStore('trustbox_code_snippet', $store->getId())));
            $trustbox->position = trim($this->_helper->getTrustboxConfigValueByStore('trustbox_position', $store->getId()));
            $trustbox->xpath    = base64_encode(trim($this->_helper->getTrustboxConfigValueByStore('trustbox_xpath', $store->getId())));
            $trustbox->page     = trim($this->_helper->getTrustboxConfigValueByStore('trustbox_page', $store->getId()));
    
            $settings = new \stdClass();
            $settings->general = $general;
            $settings->trustbox = $trustbox;
            array_push($globalSettings->stores, $settings);
        }
        
        return $globalSettings;
    }

    private function getStores() {
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $om->get('Magento\Store\Model\StoreManagerInterface');
        return $storeManager->getStores($withDefault = false);
    }
}
