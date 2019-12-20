<?php
namespace Trustpilot\Reviews\Block\System\Config;

use Trustpilot\Reviews\Helper\Data;
use Trustpilot\Reviews\Helper\PastOrders;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Trustpilot\Reviews\Helper\TrustpilotLog;

class Admin extends Field
{
    protected $_helper;
    protected $_pastOrders;
    protected $_template = 'system/config/admin.phtml';
    protected $_trustpilotLog;

    public function __construct(
        Context $context,
        Data $helper,
        PastOrders $pastOrders,
        TrustpilotLog $trustpilotLog,
        array $data = [])
    {
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
        } catch (\Throwable $throwable) {
            $description = 'Unable to get sku in Admin.php';
            $this->_trustpilotLog->error($throwable, $description, array(
                'scope' => $scope,
                'storeId' => $storeId
            ));
            return '';
        } catch (\Exception $exception) {
            $description = 'Unable to get sku in Admin.php';
            $this->_trustpilotLog->error($exception, $description, array(
                'scope' => $scope,
                'storeId' => $storeId
            ));
            return '';
        }
    }

    public function getProductName($scope, $storeId)
    {
        return $this->_helper->getFirstProduct($scope, $storeId)->getName();
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
