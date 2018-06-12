<?php
namespace Trustpilot\Reviews\Block\System\Config;
 
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
 
class Message extends Field
{
    protected $_template = 'system/config/label/message.phtml';
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }
}