<?php
namespace Trustpilot\Reviews\Model;

use Magento\Framework\Option\ArrayInterface;

class TrustpilotTemplate implements ArrayInterface
{
 public function toOptionArray()
 {
  return [
    ['value' => '56278e9abfbbba0bdcd568bc', 'label' => __('Review Collector')], 
    ['value' => '5419b6a8b0d04a076446a9ad_light', 'label' => __('Micro Review Count Light')],
    ['value' => '5419b6a8b0d04a076446a9ad_dark', 'label' => __('Micro Review Count Dark')],
  ];
 }
}
