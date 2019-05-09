<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Trustpilot\Reviews\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;
use Trustpilot\Reviews\Helper\Data;
use Trustpilot\Reviews\Helper\PastOrders;

/**
 * Main contact form block
 */
class Trustpilot extends Template
{
    protected $_helper;
    protected $_pastOrders;
    public function __construct(
        Context $context,
        Data $helper,
        PastOrders $pastOrders,
        array $data = []
    ) {
        $this->_helper = $helper;
        $this->_pastOrders = $pastOrders;
        parent::__construct($context, $data);
    }

    public function getIntegrationAppUrl()
    {
        return $this->_helper->getIntegrationAppUrl();
    }

    public function getSettings() {
        return base64_encode($this->_helper->getConfig('master_settings_field'));
    }

    public function getPageUrls() {
        return base64_encode(json_encode($this->_helper->getPageUrls()));
    }

    public function getCustomTrustBoxes()
    {
        $customTrustboxes = $this->_helper->getConfig('custom_trustboxes');
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

    public function getPastOrdersInfo() {
        $storeId = $this->_helper->getWebsiteOrStoreId();
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

    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    public function getVersion()
    {
        return $this->_helper->getVersion();
    }
}