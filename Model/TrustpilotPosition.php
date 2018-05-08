<?php
namespace Trustpilot\Reviews\Model;

use Magento\Framework\Option\ArrayInterface;

class TrustpilotPosition implements ArrayInterface
{
 public function toOptionArray()
 {
  return [
    ['value' => 'bottom: #{Y}px;left: #{X}px;', 'label' => __('Bottom-left corner')],
    ['value' => 'bottom: #{Y}px;right: #{X}px;', 'label' => __('Bottom-right corner')],
    ['value' => 'top: #{Y}px;left: #{X}px;', 'label' => __('Top-left corner')],
    ['value' => 'top: #{Y}px;right: #{X}px;', 'label' => __('Top-right corner')]
  ];
 }
}
