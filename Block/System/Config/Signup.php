<?php
namespace Trustpilot\Reviews\Block\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Trustpilot\Reviews\Helper\Data;
use Magento\Framework\Data\Form\Element\AbstractElement;
 
class Signup extends Field
{
    protected $_helper;
    protected $_signUpUrl;    

    const BUTTON_TEMPLATE = 'system/config/button/signup.phtml';

    public function __construct(
        Context $context,
        Data $helper,
        array $data = [])
    {
        $this->_helper = $helper;
        $this->_signUpUrl = $this->_helper->getGeneralConfigValue('SignUpUrl');
        parent::__construct($context, $data);
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::BUTTON_TEMPLATE);
        }
        return $this;
    }

    public function render(AbstractElement $element)
    {
        // Remove scope label
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        $this->addData(
            [
                'id'            => 'addbutton_button',
                'button_label'  => __('Sign up here')
            ]
        );
        return $this->_toHtml();
    }

    public function getSignUpUrl()
    {
        return $this->_signUpUrl;
    }
}
