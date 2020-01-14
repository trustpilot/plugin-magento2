window.addEventListener("message", this.receiveSettings);

function receiveSettings(e) {
    if (e.origin === location.origin){
        return receiveInternalData(e);
    }
    const iframe = document.getElementById('configuration_iframe');
    const attrs = iframe.dataset;
    if (e.origin !== attrs.transfer) {
        return;
    }
    const data = e.data;

    if (typeof data !== 'string') {
        return;
    }

    if (data.startsWith('sync:') || data.startsWith('showPastOrdersInitial:')) {
        const split = data.split(':');
        const action = {};
        action['action'] = 'handle_past_orders';
        action[split[0]] = split[1];
        this.submitPastOrdersCommand(action);
    } else if (data.startsWith('resync')) {
        const action = {};
        action['action'] = 'handle_past_orders';
        action['resync'] = 'resync';
        this.submitPastOrdersCommand(action);
    } else if (data.startsWith('issynced')) {
        const action = {};
        action['action'] = 'handle_past_orders';
        action['issynced'] = 'issynced';
        this.submitPastOrdersCommand(action);
    } else if (data.startsWith('check_product_skus')) {
        const split = data.split(':');
        const action = {};
        action['action'] = 'check_product_skus';
        action['skuSelector'] = split[1];
        this.submitCheckProductSkusCommand(action);
    } else if (data === 'signup_data') {
        this.sendSignupData();
    } else if (data === 'update') {
        this.updateplugin();
    } else if (data === 'reload') {
        this.reloadSettings();
    } else {
        this.handleJSONMessage(data);
    }
}

function handleJSONMessage(data) {
    const parsedData = {};
    if (tryParseJson(data, parsedData)) {
        if (parsedData.TrustBoxPreviewMode) {
            this.trustBoxPreviewMode(parsedData);
        } else if (parsedData.window) {
            this.updateIframeSize(parsedData);
        } else if (parsedData.type === 'submit') {
            this.submitSettings(parsedData);
        } else if (parsedData.trustbox) {
            const iframe = document.getElementById('trustbox_preview_frame');
            iframe.contentWindow.postMessage(JSON.stringify(parsedData.trustbox), "*");
        }
    }
}

function trustBoxPreviewMode(settings) {
    const div = document.getElementById('trustpilot-trustbox-preview');
    if (settings.TrustBoxPreviewMode.enable) {
        div.hidden = false;
    } else {
        div.hidden = true;
    }
}

function receiveInternalData(e) {
    const data = e.data;
    const parsedData = {};
    if (data && typeof data === 'string' && tryParseJson(data, parsedData)) {
        if (parsedData && parsedData.type === 'loadCategoryProductInfo') {
            requestCategoryInfo();
        }
        if (parsedData.type === 'updatePageUrls' || parsedData.type === 'newTrustBox') {
            this.submitSettings(parsedData);
        }
    }
}

function requestCategoryInfo() {
    // TODO: It brake's existing category list page filtering therefore commented until solution will be found
    // const data = {
    //     action: 'get_category_product_info',
    //     form_key: window.FORM_KEY,
    //     scope, scopeId,
    // };

    // if (typeof websiteId !== 'undefined') {
    //     data.website_id = websiteId;
    // }
    // if (typeof storeId !== 'undefined') {
    //     data.store_id = storeId;
    // }

    // const xhr = new XMLHttpRequest();
    // xhr.onreadystatechange = function() {
    //     if (xhr.readyState === 4) {
    //         if (xhr.status >= 400) {
    //             console.log(`callback error: ${xhr.response} ${xhr.status}`);
    //         } else {
    //             window.postMessage(JSON.stringify(xhr.response), window.origin);
    //         }
    //     }
    // }
    // xhr.open('POST', `${ajaxUrl}?isAjax=true`, true);
    // xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    // xhr.send(encodeSettings(data));
}

function submitPastOrdersCommand(data) {
    data['form_key'] = window.FORM_KEY;
    data['scope'] = scope;
    data['scopeId'] = scopeId;
    const xhr = new XMLHttpRequest();
    xhr.open('POST', `${ajaxUrl}?isAjax=true`, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status >= 400) {
                console.log(`callback error: ${xhr.response} ${xhr.status}`);
            } else {
                sendPastOrdersInfo(xhr.response);
            }
        }
    };
    xhr.send(encodeSettings(data));
}

function submitCheckProductSkusCommand(data) {
    data['form_key'] = window.FORM_KEY;
    data['scope'] = scope;
    data['scopeId'] = scopeId;
    const xhr = new XMLHttpRequest();
    xhr.open('POST', `${ajaxUrl}?isAjax=true`, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status >= 400) {
                console.log(`callback error: ${xhr.response} ${xhr.status}`);
            } else {
                const iframe = document.getElementById('configuration_iframe');
                iframe.contentWindow.postMessage(xhr.response, iframe.dataset.transfer);
            }
        }
    };
    xhr.send(encodeSettings(data));
}

function submitSettings(parsedData) {
    const data = {
        action: 'handle_save_changes',
        form_key: window.FORM_KEY,
        scope, scopeId
    };

    if (parsedData.type === 'updatePageUrls') {
        data.pageUrls = encodeURIComponent(JSON.stringify(parsedData.pageUrls));
    } else if (parsedData.type === 'newTrustBox') {
        data.customTrustBoxes = encodeURIComponent(JSON.stringify(parsedData));
    } else {
        data.settings = encodeURIComponent(JSON.stringify(parsedData.settings));
        const frame = document.getElementById('trustbox_preview_frame');
        if (frame) {
            frame.dataset.settings = btoa(encodeURIComponent(JSON.stringify(parsedData.settings)));
        } else {
            console.log('trustbox_preview_frame is missing. Skipping...');
        }
    }

    if (typeof websiteId !== 'undefined') {
        data.website_id = websiteId;
    }
    if (typeof storeId !== 'undefined') {
        data.store_id = storeId;
    }

    const xhr = new XMLHttpRequest();
    xhr.open('POST', `${ajaxUrl}?isAjax=true`);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.send(encodeSettings(data));
}

function encodeSettings(settings) {
    let encodedString = '';
    for (const setting in settings) {
        encodedString += `${setting}=${settings[setting]}&`
    }
    return encodedString.substring(0, encodedString.length - 1);
}

function sendSettings() {
    const iframe = document.getElementById('configuration_iframe');

    const attrs = iframe.dataset;
    const settings = JSON.parse(atob(attrs.settings));

    if (!settings.trustbox) {
        settings.trustbox = {}
    }

    settings.trustbox.pageUrls = JSON.parse(atob(attrs.pageUrls));
    settings.pluginVersion = attrs.pluginVersion;
    settings.source = attrs.source;
    settings.version = attrs.version;
    settings.basis = 'plugin';
    settings.productIdentificationOptions = JSON.parse(attrs.productIdentificationOptions);
    settings.configurationScopeTree = JSON.parse(atob(attrs.configurationScopeTree));
    settings.pluginStatus = JSON.parse(atob(attrs.pluginStatus));
    settings.isFromMarketplace = attrs.isFromMarketplace;

    if (settings.trustbox.trustboxes && attrs.sku) {
        for (trustbox of settings.trustbox.trustboxes) {
            trustbox.sku = attrs.sku;
        }
    }

    if (settings.trustbox.trustboxes && attrs.name) {
        for (trustbox of settings.trustbox.trustboxes) {
            trustbox.name = attrs.name;
        }
    }

    iframe.contentWindow.postMessage(JSON.stringify(settings), attrs.transfer);
}

function sendPastOrdersInfo(data) {
    const iframe = document.getElementById('configuration_iframe');
    const attrs = iframe.dataset;

    if (data === undefined) {
        data = attrs.pastOrders;
    }
    iframe.contentWindow.postMessage(data, attrs.transfer);
}

function updateIframeSize(settings) {
    const iframe = document.getElementById('configuration_iframe');
    if (iframe) {
        iframe.height=(settings.window.height) + "px";
    }
}

function sendSignupData() {
    const data = {
        action: 'get_signup_data',
        form_key: window.FORM_KEY,
        scope, scopeId,
    };

    const xhr = new XMLHttpRequest();
    xhr.open('POST', `${ajaxUrl}?isAjax=true`, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status >= 400) {
                console.log(`callback error: ${xhr.response} ${xhr.status}`);
            } else {
                const iframe = document.getElementById('configuration_iframe');
                iframe.contentWindow.postMessage(xhr.response, iframe.dataset.transfer);
            }
        }
    };
    xhr.send(encodeSettings(data));
}

function tryParseJson(str, out) {
    try {
        out = Object.assign(out, JSON.parse(str));
    } catch (e) {
        return false;
    }
    return true;
}
