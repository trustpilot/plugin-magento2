<?php

namespace Trustpilot\Reviews\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Trustpilot\Reviews\Helper\HttpClient;
use Trustpilot\Reviews\Helper\Data;
use \Psr\Log\LoggerInterface;
use \Magento\Store\Model\StoreManagerInterface;

class TrustpilotHttpClient extends AbstractHelper
{
    protected $_logger;
    protected $_httpClient;
    protected $_dataHelper;
    protected $_apiUrl;
    protected $_storeManager;

    public function __construct(
        LoggerInterface $logger, 
        HttpClient $httpClient, 
        StoreManagerInterface $storeManager,
        Data $dataHelper)
    {
        $this->_logger = $logger;       
        $this->_httpClient = $httpClient;
        $this->_dataHelper = $dataHelper;
        $this->_storeManager=$storeManager;
       
    }

    public function postInvitation($integrationKey, $storeId, $data = array())
    {
        $this->_apiUrl = $this->_dataHelper->getGeneralConfigValue('ApiUrl');
        $url = $this->_apiUrl . $integrationKey . '/invitation';
        $httpRequest = "POST";
        $origin = $this->_storeManager->getStore($storeId)->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
        $response = $this->_httpClient->request(
            $url,
            $httpRequest,
            $origin,
            $data
        );
        return $response;
    }

    public function postSettings($integrationKey, $data)
    {
        $this->_apiUrl = $this->_dataHelper->getGeneralConfigValue('ApiUrl');
        $url = $this->_apiUrl . $integrationKey . '/settings';
        $httpRequest = "POST";
        $origin = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
        $response = $this->_httpClient->request(
            $url,
            $httpRequest,
            $origin,
            $data
        );
        return $response;
    }

}
