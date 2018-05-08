<?php
namespace Trustpilot\Reviews\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use \Psr\Log\LoggerInterface;
use Trustpilot\Reviews\Helper\Data;

class ConfigObserver implements ObserverInterface
{
    private $_helper;
    private $_logger;

    public function __construct(LoggerInterface $logger, Data $helper)
    {
        $this->_logger = $logger;
        $this->_helper = $helper;
    }

    public function execute(EventObserver $observer)
    {
        $settings = self::getSettings();
        $service_url = $this->_helper->getGeneralConfigValue('ApiUrl') . $settings->general->key . '/settings' ;
        $curl = curl_init($service_url);
        $headers = [
            'Content-Type: application/json; charset=utf-8'
        ];
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($settings));
        $curl_response = curl_exec($curl);
        if ($curl_response === false) {
            $info = curl_getinfo($curl);
            $this->_logger->error('error occured during curl exec. Additioanl info: ' . json_encode($info));
        }
        curl_close($curl);
        $decoded = json_decode($curl_response);
        if (isset($decoded->response->status) && $decoded->response->status == 'ERROR') {
            $this->_logger->error('error occured: ' . $decoded->response->errormessage);
        }
    }

    public function getSettings() 
    {
        $general = new \stdClass();
        $general->key = trim($this->_helper->getGeneralConfigValue('key'));;

        $trustbox = new \stdClass();
        $trustbox->enabled = trim($this->_helper->getTrustBoxConfigValue('trustbox_enable'));
        $trustbox->locale = trim($this->_helper->getTrustBoxConfigValue('trustbox_locale'));
        $trustbox->template = trim($this->_helper->getTrustBoxConfigValue('trustbox_template'));
        $trustbox->position = trim($this->_helper->getTrustBoxConfigValue('trustbox_position'));
        $trustbox->paddingx = trim($this->_helper->getTrustBoxConfigValue('trustbox_paddingx'));
        $trustbox->paddingy = trim($this->_helper->getTrustBoxConfigValue('trustbox_paddingy'));

        $settings = new \stdClass();
        $settings->source = 'Magento2';
        $settings->general = $general;
        $settings->trustbox = $trustbox;
        
        return $settings;
    }
}
