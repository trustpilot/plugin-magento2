<?php

namespace Trustpilot\Reviews\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface as StoreScopeInterface;

class Data extends AbstractHelper
{

    const XML_PATH_TRUSTPILOT_GENERAL = 'trustpilotGeneral/general/';
    const XML_PATH_TRUSTPILOT_TRUSTBOX = 'trustpilotTrustbox/trustbox/';

    protected $_request;

    public function __construct(Context $context)
    {
        parent::__construct($context);
        $this->_request = $context->getRequest();
    }

    public function getGeneralConfigValue($value)
    {
        if ($this->getWebsiteOrStoreId()) {
            return $this->scopeConfig->getValue(self::XML_PATH_TRUSTPILOT_GENERAL . $value, $this->getScope(), $this->getWebsiteOrStoreId());
        }
        return $this->scopeConfig->getValue(self::XML_PATH_TRUSTPILOT_GENERAL . $value, StoreScopeInterface::SCOPE_STORE);
    }
    
    public function getTrustBoxConfigValue($value)
    {
        if ($this->getWebsiteOrStoreId()) {
            return $this->scopeConfig->getValue(self::XML_PATH_TRUSTPILOT_TRUSTBOX . $value, $this->getScope(), $this->getWebsiteOrStoreId());
        }
        return $this->scopeConfig->getValue(self::XML_PATH_TRUSTPILOT_TRUSTBOX . $value, StoreScopeInterface::SCOPE_STORE);
    }

    public function getTrustboxConfigValueByStore($value, $storeId)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_TRUSTPILOT_TRUSTBOX . $value, StoreScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getGeneralConfigValueByStore($value, $storeId)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_TRUSTPILOT_GENERAL . $value, StoreScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getWebsiteOrStoreId() 
    {
        if ($this->_request->getParam('store')) {
            return (int) $this->_request->getParam('store', 0);
        } else if ($this->_request->getParam('website')) {
            return (int) $this->_request->getParam('website', 0);
        }
        return 0;
    }

    public function getScope()
    {
        return $this->_request->getParam('store') ? StoreScopeInterface::SCOPE_STORE : 
        ($this->_request->getParam('website') ? StoreScopeInterface::SCOPE_WEBSITE : 'default');
    }
    
    public function getTrustBoxConfig()
    {
        $snippet  = trim($this->getTrustBoxConfigValue('trustbox_code_snippet'));
        $position = trim($this->getTrustBoxConfigValue('trustbox_position'));
        $xpath    = trim($this->getTrustBoxConfigValue('trustbox_xpath')); 

        $data = [
            'snippet'  => base64_encode($snippet),
            'position' => $position,
            'xpath'    => base64_encode($xpath)
        ];
        return $data;
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
}
