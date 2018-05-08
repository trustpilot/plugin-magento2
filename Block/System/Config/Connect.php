<?php
namespace Trustpilot\Reviews\Block\System\Config;
 
use Magento\Framework\App\Config\ScopeConfigInterface;
 
class Connect extends \Magento\Config\Block\System\Config\Form\Field
{
     const BUTTON_TEMPLATE = 'system/config/button/connect.phtml';
     /**
      * Set template to itself
      *
      * @return $this
      */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::BUTTON_TEMPLATE);
        }
        return $this;
    }
    /**
     * Render button
     *
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        // Remove scope label
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }
     /**
      * Get the button and scripts contents
      *
      * @return string
      */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->addData(
            [
                'id'        => 'conect_button',
                'button_label'     => __('Get installation key')
            ]
        );
        return $this->_toHtml();
    }
    public function getConnectUrl()
    {
        return "https://ecommerce-invitations.b2b.trustpilot.com/#/magento";
    }
}
