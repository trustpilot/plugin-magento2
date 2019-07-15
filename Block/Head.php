<?php
namespace Trustpilot\Reviews\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;
use Trustpilot\Reviews\Helper\Data;

class Head extends Template
{
    protected $_helper;
    protected $_scriptUrl;
    protected $_tbWidgetScriptUrl;
    protected $_previewScriptUrl;
    protected $_previewCssUrl;
    
    public function __construct(
        Context $context,
        Data $helper,
        array $data = []
    ) {
        $this->_helper              = $helper;
        $this->_scriptUrl               = \Trustpilot\Reviews\Model\Config::TRUSTPILOT_SCRIPT_URL;
        $this->_tbWidgetScriptUrl       = \Trustpilot\Reviews\Model\Config::TRUSTPILOT_WIDGET_SCRIPT_URL;
        $this->_previewScriptUrl        = \Trustpilot\Reviews\Model\Config::TRUSTPILOT_PREVIEW_SCRIPT_URL;
        $this->_previewCssUrl           = \Trustpilot\Reviews\Model\Config::TRUSTPILOT_PREVIEW_CSS_URL;

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

    public function getPreviewScriptUrl()
    {
        return $this->_previewScriptUrl;
    }

    public function getPreviewCssUrl()
    {
        return $this->_previewCssUrl;
    }

    public function getInstallationKey()
    {
        $scope = $this->_helper->getScope();
        $storeId = $this->_helper->getWebsiteOrStoreId();
        return $this->_helper->getKey($scope, $storeId);
    }
}
