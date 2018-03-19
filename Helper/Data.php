<?php

namespace Trustpilot\Reviews\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    const XML_PATH_TRUSTPILOT_GENERAL = 'trustpilot/general/';

    public function getConfigValue($value)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_TRUSTPILOT_GENERAL
            . $value, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }
}
