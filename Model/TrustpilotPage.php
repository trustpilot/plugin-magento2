<?php

namespace Trustpilot\Reviews\Model;

use Magento\Framework\Option\ArrayInterface;

class TrustpilotPage implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'trustpilot_trustbox_homepage', 'label' => __('Landing')],
            ['value' => 'trustpilot_trustbox_category', 'label' => __('Category')],
            ['value' => 'trustpilot_trustbox_product', 'label' => __('Product')]
        ];
    }
}
