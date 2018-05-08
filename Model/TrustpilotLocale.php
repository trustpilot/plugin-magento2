<?php
namespace Trustpilot\Reviews\Model;

use Magento\Framework\Option\ArrayInterface;

class TrustpilotLocale implements ArrayInterface
{
 public function toOptionArray()
 {
  return [
    ['value' => 'en-US', 'label' => __('English (United States)')],
    ['value' => 'da-DK', 'label' => __('Danish')],
    ['value' => 'de-AT', 'label' => __('German (Austria)')],
    ['value' => 'de-CH', 'label' => __('German (Switzerland)')],
    ['value' => 'de-DE', 'label' => __('German')],
    ['value' => 'en-AU', 'label' => __('English (Australia)')],
    ['value' => 'en-CA', 'label' => __('English (Canada)')] ,
    ['value' => 'en-GB', 'label' => __('English (United Kingdom)')],
    ['value' => 'en-IE', 'label' => __('English (Ireland)')],
    ['value' => 'en-NZ', 'label' => __('English (New Zealand)')],
    ['value' => 'es-ES', 'label' => __('Spanish')] ,
    ['value' => 'fi-FI', 'label' => __('Finnish')],
    ['value' => 'fr-BE', 'label' => __('French (Belgium)')],
    ['value' => 'fr-FR', 'label' => __('French')],
    ['value' => 'it-IT', 'label' => __('Italian')],
    ['value' => 'ja-JP', 'label' => __('Japanese')],
    ['value' => 'nb-NO', 'label' => __('Norwegian')],
    ['value' => 'nl-BE', 'label' => __('Dutch (Belgium)')],
    ['value' => 'nl-NL', 'label' => __('Dutch')],
    ['value' => 'pl-PL', 'label' => __('Polish')],
    ['value' => 'pt-BR', 'label' => __('Portuguese (Brazil)')],
    ['value' => 'pt-PT', 'label' => __('Portuguese')],
    ['value' => 'ru-RU', 'label' => __('Russian')],
    ['value' => 'sv-SE', 'label' => __('Swedish')],
    ['value' => 'zh-CN', 'label' => __('Mandarin Chinese')]
  ];
 }
}