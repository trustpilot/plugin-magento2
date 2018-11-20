<?php
namespace Trustpilot\Reviews\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Registry;
use Trustpilot\Reviews\Helper\Data;

class Trustbox extends Template
{
    protected $_helper;
    protected $_registry;

    public function __construct(
        Context $context,
        Data $helper,
        Registry $registry,
        array $data = [])
    {
        $this->_helper = $helper;
        $this->_registry = $registry;
        parent::__construct($context, $data);
    }

    public function getTrustBoxPage($block)
    {
        $page = trim($this->_helper->getTrustBoxConfigValue('trustbox_page'));
        return strcmp($page, $block) === 0 ? 'true' : 'false';
    }
    
    public function getTrustBoxConfig()
    {
        $data = $this->_helper->getTrustBoxConfig();
        $current_product = $this->_registry->registry('current_product');
        if ($current_product) {
            $sku = $current_product->getSku();
            $data['sku'] = $sku;
            $name = $current_product->getName();
            $data['name'] = $name;
        }
        return json_encode($data, JSON_HEX_APOS);
    }
    
    public function getTrustBoxStatus()
    {
        return trim($this->_helper->getTrustBoxConfigValue('trustbox_enable'));
    }
}
