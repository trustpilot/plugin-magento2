<?php
namespace Trustpilot\Reviews\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;
use Trustpilot\Reviews\Helper\Data;

class Head extends Template
{
    protected $_helper;

    protected $_scriptUrl;
    
    public function __construct(
        Context $context,
        Data $helper,
        array $data = []
    ) {
        $this->_helper = $helper;
        $this->_scriptUrl = 'https://invitejs.trustpilot.com/tp.min.js';
        parent::__construct($context, $data);
    }

    public function getScriptUrl()
    {
        return $this->_scriptUrl;
    }

    public function getInstallationKey()
    {
		$key = trim($this->_helper->getConfigValue('key'));
        return $key;
    }
}
