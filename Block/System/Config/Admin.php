<?php
namespace Trustpilot\Reviews\Block\System\Config;

use Trustpilot\Reviews\Helper\Data;
use Trustpilot\Reviews\Helper\PastOrders;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Admin extends Field
{
    protected $_helper;
    protected $_pastOrders;
    protected $_template = 'system/config/admin.phtml';

    public function __construct(
        Context $context,
        Data $helper,
        PastOrders $pastOrders,
        array $data = [])
    {
        $this->_helper = $helper;
        $this->_pastOrders = $pastOrders;
        parent::__construct($context, $data);
    }

    public function getIntegrationAppUrl()
    {
        return $this->_helper->getIntegrationAppUrl();
    }

    public function getSettings($storeId) {
        return base64_encode($this->_helper->getConfig('master_settings_field', $storeId));
    }

    public function getPageUrls($storeId) {
        return base64_encode(json_encode($this->_helper->getPageUrls($storeId)));
    }

    public function getCustomTrustBoxes($storeId)
    {
        $customTrustboxes = $this->_helper->getConfig('custom_trustboxes', $storeId);
        if ($customTrustboxes) {
            return $customTrustboxes;
        }
        return "{}";
    }

    public function getProductIdentificationOptions() {
        return $this->_helper->getProductIdentificationOptions();
    }

    public function getStoreInformation() {
        return $this->_helper->getStoreInformation();
    }

    public function getPastOrdersInfo($storeId) {
        $info = $this->_pastOrders->getPastOrdersInfo($storeId);
        $info['basis'] = 'plugin';
        return json_encode($info);
    }

    public function getSku()
    {
        return $this->_helper->getFirstProduct()->getSku();
    }

    public function getProductName()
    {
        return $this->_helper->getFirstProduct()->getName();
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    public function getVersion()
    {
        return $this->_helper->getVersion();
    }
}