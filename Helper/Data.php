<?php

namespace Trustpilot\Reviews\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    const XML_PATH_TRUSTPILOT_GENERAL = 'trustpilotGeneral/general/';
    const XML_PATH_TRUSTPILOT_TRUSTBOX = 'trustpilotTrustbox/trustbox/';

    public function getGeneralConfigValue($value)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_TRUSTPILOT_GENERAL
            . $value, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }
    
    public function getTrustBoxConfigValue($value)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_TRUSTPILOT_TRUSTBOX . $value, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }
}
