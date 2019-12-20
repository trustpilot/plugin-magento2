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
use Trustpilot\Reviews\Helper\TrustpilotLog;

/**
 * Main contact form block
 */
class Trustpilot extends Template
{
    protected $_helper;
    protected $_pastOrders;
    protected $_trustpilotLog;
    public function __construct(
        Context $context,
        Data $helper,
        PastOrders $pastOrders,
        TrustpilotLog $trustpilotLog,
        array $data = []
    ) {
        $this->_helper = $helper;
        $this->_pastOrders = $pastOrders;
        $this->_trustpilotLog = $trustpilotLog;
        parent::__construct($context, $data);
    }

    public function getIntegrationAppUrl()
    {
        return $this->_helper->getIntegrationAppUrl();
    }

    public function getSettings($scope, $storeId) {
        return base64_encode($this->_helper->getConfig('master_settings_field', $storeId, $scope));
    }

    public function getPageUrls($scope, $storeId) {
        return base64_encode(json_encode($this->_helper->getPageUrls($scope, $storeId)));
    }

    public function getCustomTrustBoxes($scope, $storeId)
    {
        $customTrustboxes = $this->_helper->getConfig('custom_trustboxes', $storeId, $scope);
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

    public function getPluginStatus($scope, $storeId) {
        return base64_encode($this->_helper->getConfig('plugin_status', $storeId, $scope));
    }

    public function getPastOrdersInfo($scope, $storeId) {
        $info = $this->_pastOrders->getPastOrdersInfo($scope, $storeId);
        $info['basis'] = 'plugin';
        return json_encode($info);
    }

    public function getSku($scope, $storeId)
    {
        try {
            $product = $this->_helper->getFirstProduct($scope, $storeId);
            if ($product) {
                $skuSelector = json_decode($this->_helper->getConfig('master_settings_field', $storeId, $scope))->skuSelector;
                $productId = \Trustpilot\Reviews\Model\Config::TRUSTPILOT_PRODUCT_ID_PREFIX . $this->_helper->loadSelector($product, 'id');
                if ($skuSelector == 'none') $skuSelector = 'sku';
                return $this->_helper->loadSelector($product, $skuSelector) . ',' . $productId;
            }
        } catch (\Throwable $exception) {
            $description = 'Unable to get sku in Trustpilot.php';
            $this->_trustpilotLog->error($exception, $description, array('scope' => $scope, 'storeId' => $storeId));
            return '';
        } catch (\Exception $exception) {
            $description = 'Unable to get sku in Trustpilot.php';
            $this->_trustpilotLog->error($exception, $description, array('scope' => $scope, 'storeId' => $storeId));
            return '';
        }
    }

    public function getProductName($scope, $storeId)
    {
        return $this->_helper->getFirstProduct($scope, $storeId)->getName();
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
