<?php
namespace Trustpilot\Reviews\Block\System\Config;
 
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Trustpilot\Reviews\Helper\Data;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Connect extends Field
{
    protected $_helper;
    protected $_connectUrl;

    const BUTTON_TEMPLATE = 'system/config/button/connect.phtml';

    public function __construct(
        Context $context,
        Data $helper,
        array $data = [])
    {
        $this->_helper = $helper;
        $this->_connectUrl = $this->_helper->getGeneralConfigValue('ConnectUrl');
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
                'id'            => 'conect_button',
                'button_label'  => __('Get installation key')
            ]
        );
        return $this->_toHtml();
    }
    
    public function getConnectUrl()
    {
        return $this->_connectUrl;
    }
}
