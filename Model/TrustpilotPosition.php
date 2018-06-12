<?php
namespace Trustpilot\Reviews\Model;

use Magento\Framework\Option\ArrayInterface;

class TrustpilotPosition implements ArrayInterface
{
 public function toOptionArray()
 {
  return [
    ['value' => 'before', 'label' => __('Above element')],
    ['value' => 'after', 'label' => __('Below element')]
  ];
 }
}
