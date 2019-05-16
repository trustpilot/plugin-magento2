<?php
namespace Trustpilot\Reviews\Block\System\Config;
 
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
 
class Message extends Field
{
    protected $_template = 'system/config/message.phtml';
    protected function _getElementHtml(AbstractElement $element)
    {
        if (!$this->_storeManager->isSingleStoreMode()) {
            return $this->_toHtml();
        } else {
            return null;
        }
    }
}