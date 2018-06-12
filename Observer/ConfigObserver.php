<?php
namespace Trustpilot\Reviews\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use \Psr\Log\LoggerInterface;
use Trustpilot\Reviews\Helper\Data;

class ConfigObserver implements ObserverInterface
{
    private $_helper;
    private $_logger;

    public function __construct(LoggerInterface $logger, Data $helper)
    {
        $this->_logger = $logger;
        $this->_helper = $helper;
    }

    public function execute(EventObserver $observer)
    {
        $settings = self::getSettings();
        $service_url = $this->_helper->getGeneralConfigValue('ApiUrl') . $this->_helper->getGeneralConfigValue('key') . '/settings' ;
        $curl = curl_init($service_url);
        $headers = [
            'Content-Type: application/json; charset=utf-8'
        ];
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($settings));
        $curl_response = curl_exec($curl);
        if ($curl_response === false) {
            $info = curl_getinfo($curl);
            $this->_logger->error('error occured during curl exec. Additioanl info: ' . json_encode($info));
        }
        curl_close($curl);
        $decoded = json_decode($curl_response);
        if (isset($decoded->response->status) && $decoded->response->status == 'ERROR') {
            $this->_logger->error('error occured: ' . $decoded->response->errormessage);
        }
    }

    public function getSettings() 
    {
        $globalSettings = new \stdClass();
        $globalSettings->source         = 'Magento2';
        $globalSettings->pluginVersion  = $this->_helper->getGeneralConfigValue('ReleaseNumber');
        $globalSettings->magentoVersion = 'Magento-'.$this->getVersion();
        $id = 0;
        
        $stores = $this->getStores();
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
            $trustbox->enabled = trim($this->_helper->getTrustBoxConfigValue('trustbox_enable'));
            $trustbox->snippet  = base64_encode(trim($this->_helper->getTrustBoxConfigValue('trustbox_code_snippet')));
            $trustbox->position = trim($this->_helper->getTrustBoxConfigValue('trustbox_position'));
            $trustbox->paddingx = trim($this->_helper->getTrustBoxConfigValue('trustbox_paddingx'));
            $trustbox->paddingy = trim($this->_helper->getTrustBoxConfigValue('trustbox_paddingy'));
    
            $settings = new \stdClass();
            $settings->general = $general;
            $settings->trustbox = $trustbox;
            
            $globalSettings->$id = $settings;
            $id = $id + 1;
        }
        return $globalSettings;
    }

    private function getVersion() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        return $productMetadata->getVersion();
    }

    private function getStores() {
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $om->get('Magento\Store\Model\StoreManagerInterface');
        return $storeManager->getStores($withDefault = false);
    }
}
