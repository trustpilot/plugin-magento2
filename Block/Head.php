<?php
namespace Trustpilot\Reviews\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;
use Trustpilot\Reviews\Helper\Data;

class Head extends Template
{
    protected $_helper;
    protected $_tbWidgetScriptUrl;
    protected $_scriptUrl;
    
    public function __construct(
        Context $context,
        Data $helper,
        array $data = []
    ) {
        $this->_helper              = $helper;
        $this->_scriptUrl           = $this->_helper->getGeneralConfigValue('ScriptUrl');
        $this->_tbWidgetScriptUrl   = $this->_helper->getGeneralConfigValue('WidgetUrl');

        parent::__construct($context, $data);
    }

    public function getScriptUrl()
    {
        return $this->_scriptUrl;
    }

    public function getWgxpathUrl()
    {
        return $this->getViewFileUrl('Trustpilot_Reviews::js/wgxpath.install.js');
    }

    public function getWidgetScriptUrl()
    {
        return $this->_tbWidgetScriptUrl;
    }

    public function getInstallationKey()
    {
        return trim($this->_helper->getGeneralConfigValue('key'));
    }

    public function getTrustBoxStatus()
    {
        return trim($this->_helper->getTrustBoxConfigValue('trustbox_enable'));
    }
}
