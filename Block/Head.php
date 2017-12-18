<?php
namespace Trustpilot\Reviews\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;
use Trustpilot\Reviews\Helper\Data;

class Head extends Template
{
    protected $_helper;

    protected $_script_url;
    
    public function __construct(
        Context $context,
        Data $helper,
        array $data = []
    ) {
        $this->_helper = $helper;
        $this->_script_url = 'https://invitejs.trustpilot.com/tp.min.js';
        parent::__construct($context, $data);
    }

    public function renderScript()
    {
        $key = trim($this->_helper->getConfigValue('key'));
        return '
        <script type="text/javascript">
            (function(w,d,s,r,n){w.TrustpilotObject=n;w[n]=w[n]||function(){(w[n].q=w[n].q||[]).push(arguments)};
            a=d.createElement(s);a.async=1;a.src=r;a.type=\'text/java\'+s;f=d.getElementsByTagName(s)[0];
            f.parentNode.insertBefore(a,f)})(window,document,\'script\', \''.$this->_script_url.'\', \'tp\');
            tp(\'register\',\''.$key.'\');
        </script>';
    }
}
