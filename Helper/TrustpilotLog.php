<?php

namespace Trustpilot\Reviews\Helper;
use \Psr\Log\LoggerInterface;
use \Trustpilot\Reviews\Model\Config;

class TrustpilotLog
{
    protected $_trustpilotHttpClient;
    protected $_logger;

    public function __construct(TrustpilotHttpClient $trustpilotHttpClient, LoggerInterface $logger)
    {
        $this->_trustpilotHttpClient = $trustpilotHttpClient;
        $this->_logger = $logger;
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
        $this->_trustpilotHttpClient->postLog($errorObject, $storeId);

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
}
