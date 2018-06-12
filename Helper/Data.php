<?php

namespace Trustpilot\Reviews\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    const XML_PATH_TRUSTPILOT_GENERAL = 'trustpilotGeneral/general/';
    const XML_PATH_TRUSTPILOT_TRUSTBOX = 'trustpilotTrustbox/trustbox/';

    public function getGeneralConfigValue($value)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_TRUSTPILOT_GENERAL . $value, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    public function getTrustBoxConfigValue($value)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_TRUSTPILOT_TRUSTBOX . $value, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
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
}
