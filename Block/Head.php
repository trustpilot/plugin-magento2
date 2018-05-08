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
        $this->_helper = $helper;
        $this->_scriptUrl = $this->_helper->getGeneralConfigValue('ScriptUrl');
        $this->_tbWidgetScriptUrl = $this->_helper->getGeneralConfigValue('WidgetUrl');
        parent::__construct($context, $data);
    }

    public function getScriptUrl()
    {
        return $this->_scriptUrl;
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

    public function getTrustBoxConfig()
    {
        $locale   = trim($this->_helper->getTrustBoxConfigValue('trustbox_locale'));
        $template = trim($this->_helper->getTrustBoxConfigValue('trustbox_template'));
        $position = trim($this->_helper->getTrustBoxConfigValue('trustbox_position'));
        $paddingx = trim($this->_helper->getTrustBoxConfigValue('trustbox_paddingx'));
        $paddingy = trim($this->_helper->getTrustBoxConfigValue('trustbox_paddingy'));

        if (strrpos($template, "_") == false) {
            $theme = '';
        } else {
            $theme    = substr($template, strrpos($template, "_") + 1, strlen($template));
            $template = substr($template, 0, strrpos($template, "_"));
        }
        $data = array(
            'theme' => $theme,
            'locale' => $locale,
            'template' => $template,
            'position' => $position,
            'paddingx' => $paddingx,
            'paddingy' => $paddingy
        );
        return json_encode($data, JSON_HEX_APOS);
    }
}
