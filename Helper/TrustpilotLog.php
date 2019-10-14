<?php

namespace Trustpilot\Reviews\Helper;
use \Psr\Log\LoggerInterface;
use \Trustpilot\Reviews\Model\Config;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\UrlInterface;

class TrustpilotLog
{
    protected $_httpClient;
    protected $_logger;
    protected $_apiUrl;
    protected $_storeManager;

    public function __construct(
        HttpClient $httpClient,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    )
    {
        $this->_logger = $logger;
        $this->_httpClient = $httpClient;
        $this->_storeManager = $storeManager;
        $this->_apiUrl = \Trustpilot\Reviews\Model\Config::TRUSTPILOT_API_URL;
    }

    public function error($e, $description, $optional = array()) {
        $errorObject = array(
            'error' => $e->getMessage(),
            'description' => $description,
            'platform' => 'Magento2',
            'version' => Config::TRUSTPILOT_PLUGIN_VERSION,
            'method' => $this->getMethodName($e),
            'trace' => $e->getTraceAsString(),
            'variables' => $optional
        );

        $storeId = in_array('storeId', $optional) ? $optional['storeId'] : false;
        $this->postLog($errorObject, $storeId);

        // Don't log stack trace locally
        unset($errorObject['trace']);
        // Logs to var/log/system.log
        $this->_logger->error(json_encode($errorObject));
    }

    private function getMethodName($e) {
        $trace = $e->getTrace();
        if (array_key_exists(0, $trace)) {
            $firstNode = $trace[0];
            if (array_key_exists('function', $firstNode)) {
                return $firstNode['function'];
            }
        }
        return '';
    }

    private function postLog($data, $storeId = null)
    {
        try {
            $origin = $storeId ? $this->_storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_WEB) : '';
            return $this->_httpClient->request(
                $this->_apiUrl . 'log',
                'POST',
                $origin,
                $data
            );
        } catch (\Exception $e) {
            return false;
        }
    }
}
