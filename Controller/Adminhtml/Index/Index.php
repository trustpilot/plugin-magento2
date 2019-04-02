<?php

namespace Trustpilot\Reviews\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Trustpilot\Reviews\Helper\Data;
use Trustpilot\Reviews\Helper\PastOrders;
use Trustpilot\Reviews\Helper\Products;

class Index extends Action
{
    protected $_helper;
    protected $_pastOrders;
    protected $_products;

    public function __construct(
        Action\Context $context,
        Data $helper,
        PastOrders $pastOrders,
        Products $products)
    {
        parent::__construct($context);
        $this->_helper = $helper;
        $this->_pastOrders = $pastOrders;
        $this->_products = $products;
    }

    public function execute()
    {
        session_write_close();
        if ($this->getRequest()->isAjax()) {
            $post = $this->getRequest()->getPostValue();
            $scope = $post['scope'];
            $scopeId = $post['scopeId'];
            switch ($post["action"]) {
                case 'handle_save_changes':
                    if (array_key_exists('settings', $post)) {
                        $this->setConfig('master_settings_field', $post['settings'], $scope, $scopeId);
                        break;
                    } else if (array_key_exists('pageUrls', $post)) {
                        $this->setConfig('page_urls', $post['pageUrls'], $scope, $scopeId);
                        break;
                    } else if (array_key_exists('customTrustBoxes', $post)) {
                        $this->setConfig('custom_trustboxes', $post['customTrustBoxes'], $scope, $scopeId);
                        break;
                    }
                    break;
                case 'handle_past_orders':
                    if (array_key_exists('sync', $post)) {
                        $this->_pastOrders->sync($post["sync"], $scope, $scopeId);
                        $output = $this->_pastOrders->getPastOrdersInfo($scopeId);
                        $output['basis'] = 'plugin';
                        $output['pastOrders']['showInitial'] = false;
                        $this->getResponse()->setBody(json_encode($output));
                        break;
                    } else if (array_key_exists('resync', $post)) {
                        $this->_pastOrders->resync($scope, $scopeId);
                        $output = $this->_pastOrders->getPastOrdersInfo($scopeId);
                        $output['basis'] = 'plugin';
                        $this->getResponse()->setBody(json_encode($output));
                        break;
                    } else if (array_key_exists('issynced', $post)) {
                        $output = $this->_pastOrders->getPastOrdersInfo($scopeId);
                        $output['basis'] = 'plugin';
                        $this->getResponse()->setBody(json_encode($output));
                        break;
                    } else if (array_key_exists('showPastOrdersInitial', $post)) {
                        $this->_helper->setConfig("show_past_orders_initial", $post["showPastOrdersInitial"], $scope, $scopeId);
                        $this->getResponse()->setBody('true');
                        break;
                    }
                    break;
                case 'check_product_skus':
                    $result = $this->_products->checkSkus();
                    $this->getResponse()->setBody(json_encode($result));
                    break;
            }
        }

        return false;
    }

    private function setConfig($key, $value, $scope, $scopeId)
    {
        $this->_helper->setConfig($key, $value, $scope, $scopeId);
        $this->getResponse()->setBody($value);
    }
}
