<?php

namespace Trustpilot\Invitation\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    const XML_PATH_INVITATION_GENERAL = 'invitation/general/';

    public function getConfigValue($value)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_INVITATION_GENERAL . $value, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}