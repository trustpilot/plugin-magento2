<?php

namespace Trustpilot\Reviews\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Trustpilot\Reviews\Helper\HttpClient;
use Trustpilot\Reviews\Helper\TrustpilotPluginStatus;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\UrlInterface;

class TrustpilotHttpClient extends AbstractHelper
{
    protected $_httpClient;
    protected $_apiUrl;
    protected $_storeManager;
    protected $_pluginStatus;

    public function __construct(
        HttpClient $httpClient, 
        StoreManagerInterface $storeManager,
        TrustpilotPluginStatus $pluginStatus)
    {
        $this->_pluginStatus = $pluginStatus;
        $this->_httpClient = $httpClient;
        $this->_storeManager = $storeManager;
        $this->_apiUrl = \Trustpilot\Reviews\Model\Config::TRUSTPILOT_API_URL;
    }

    public function post($url, $origin, $data, $storeId)
    {
        $httpRequest = 'POST';
        $response = $this->_httpClient->request(
            $url,
            $httpRequest,
            $origin,
            $data
        );
        if ($response['code'] > 250 && $response['code'] < 254) {
            $this->_pluginStatus->setPluginStatus($response, $storeId);
        }
        return $response;
    }

    public function buildUrl($key, $endpoint)
    {
        return $this->_apiUrl . $key . $endpoint;
    }

    public function checkStatusAndPost($url, $origin, $data, $storeId)
    {
        $code = $this->_pluginStatus->checkPluginStatus($origin, $storeId);
        if ($code > 250 && $code < 254) {
            return array(
                'code' => $code,
            );
        }
        return $this->post($url, $origin, $data, $storeId);
    }

    public function postInvitation($key, $storeId, $data = array())
    {
        $origin = $this->_storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_WEB);
        return $this->checkStatusAndPost($this->buildUrl($key, '/invitation'), $origin, $data, $storeId);
    }

    public function postSettings($key, $data)
    {
        $origin = $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);
        return $this->post($this->buildUrl($key, '/settings'), $origin, $data, $storeId);
    }

    public function postBatchInvitations($key, $storeId, $data = array())
    {
        $origin = $this->_storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_WEB);
        return $this->checkStatusAndPost($this->buildUrl($key, '/batchinvitations'), $origin, $data, $storeId);
    }
}
