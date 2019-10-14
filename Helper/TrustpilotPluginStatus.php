<?php

namespace Trustpilot\Reviews\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Trustpilot\Reviews\Helper\Data;

class TrustpilotPluginStatus extends AbstractHelper
{
    const TRUSTPILOT_SUCCESSFUL_STATUS = 200;
    private $_helper;

    public function __construct(Data $helper) 
    {
        $this->_helper = $helper;
    }

    public function setPluginStatus($response, $storeId)
    {
        $data = json_encode(
            array(
                'pluginStatus' => $response['code'],
                'blockedDomains' => isset($response['data']) ? $response['data'] : array(),
            )
        );
        $this->_helper->setConfig('plugin_status', $data, 'stores', $storeId);
    }
    public function checkPluginStatus($origin, $storeId)
    {
        $data = json_decode($this->_helper->getConfig('plugin_status', $storeId, 'stores'));
        if (in_array(parse_url($origin, PHP_URL_HOST), $data->blockedDomains)) {
            return $data->pluginStatus;
        }
        return self::TRUSTPILOT_SUCCESSFUL_STATUS;
    }
}
