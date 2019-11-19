<?php

namespace Trustpilot\Reviews\Model;

class Config 
{
    // example of usage - \Trustpilot\Reviews\Model\Config::TRUSTPILOT_IS_FROM_MARKETPLACE
    const WITH_PRODUCT_DATA                     = 'WITH_PRODUCT_DATA';
    const WITHOUT_PRODUCT_DATA                  = 'WITHOUT_PRODUCT_DATA';
    const TRUSTPILOT_MASTER_FIELD               = 'trustpilot_master_settings_field';
    const TRUSTPILOT_ORDER_DATA                 = 'OrderData';
    const TRUSTPILOT_SYNC_IN_PROGRESS           = 'trustpilot_sync_in_progress';
    const TRUSTPILOT_SHOW_PAST_ORDERS_INITIAL   = 'trustpilot_show_past_orders_initial';
    const TRUSTPILOT_PAST_ORDERS_FIELD          = 'trustpilot_past_orders';
    const TRUSTPILOT_FAILED_ORDERS_FIELD        = 'trustpilot_failed_orders';
    const TRUSTPILOT_GENERAL_CONFIGURATION      = 'general';
    const TRUSTPILOT_TRUSTBOX_CONFIGURATION     = 'trustbox';
    const TRUSTPILOT_INTEGRATION_KEY            = 'key';
    const TRUSTPILOT_PLUGIN_VERSION             = '2.6.522';
    const TRUSTPILOT_SCRIPT                     = 'TrustpilotScriptUrl';
    const TRUSTPILOT_INTEGRATION_APP            = 'IntegrationAppUrl';
    const TRUSTPILOT_WIDGET_SCRIPT              = 'WidgetScriptUrl';
    const TRUSTPILOT_PREVIEW_SCRIPT             = 'PreviewScriptUrl';
    const TRUSTPILOT_PREVIEW_CSS                = 'PreviewCssUrl';
    const TRUSTPILOT_PLUGIN_URL                 = 'https://ecommplugins-pluginrepo.trustpilot.com/magento2/trustpilot.magento2.tgz';
    const TRUSTPILOT_API_URL                    = 'https://invitejs.trustpilot.com/api/';
    const TRUSTPILOT_SCRIPT_URL                 = 'https://invitejs.trustpilot.com/tp.min.js';
    const TRUSTPILOT_INTEGRATION_APP_URL        = '//ecommscript-integrationapp.trustpilot.com';
    const TRUSTPILOT_WIDGET_SCRIPT_URL          = '//widget.trustpilot.com/bootstrap/v5/tp.widget.bootstrap.min.js';
    const TRUSTPILOT_PREVIEW_SCRIPT_URL         = '//ecommplugins-scripts.trustpilot.com/v2.1/js/preview.min.js';
    const TRUSTPILOT_PREVIEW_CSS_URL            = '//ecommplugins-scripts.trustpilot.com/v2.1/css/preview.min.css';
    const TRUSTPILOT_TRUSTBOX_PREVIEW_URL       = '//ecommplugins-trustboxpreview.trustpilot.com/v1.0/trustboxpreview.min.js';
    const TRUSTPILOT_IS_FROM_MARKETPLACE        = 'false';
    const TRUSTPILOT_PRODUCT_ID_PREFIX          = 'TRUSTPILOT_SKU_VALUE_';
}
