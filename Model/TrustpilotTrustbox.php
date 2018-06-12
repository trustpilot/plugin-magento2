<?php
namespace Trustpilot\Reviews\Model;

use Magento\Framework\Option\ArrayInterface;

class TrustpilotTrustbox implements ArrayInterface
{
 public function toOptionArray()
 {
  return [
    ['value' => 'disabled', 'label' => __('Disabled')],
    ['value' => 'enabled', 'label' => __('Enabled')]
  ];
 }
}
