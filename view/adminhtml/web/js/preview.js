class TrustpilotPreview {
    static toggleSpinners(on = true) {
            const spinner = document.getElementById("preview-spinner");
            if (spinner) {
                spinner.style.display = (on) ? "block" : "none";
            }  
    }

    static init(cssUrl) {
        const field = TrustpilotPreview.getXPathField();
        const iframe = TrustpilotPreview.getIFrame();
        const trustboxField = TrustpilotPreview.getTrustboxField();
        if (iframe && field) {
            TrustpilotPreview.injectCss(iframe.contentDocument, cssUrl);
            // mark selected
            if (field.value) {
                const el = TrustpilotPreview.getElementByXPath(field.value, iframe.contentDocument);
                TrustpilotPreview.removeTrustBox(iframe.contentDocument);
                TrustpilotPreview.renderTrustBox(field.value, iframe);
            }
            let target;
            const iframeWin = iframe.contentWindow;
            iframeWin.onmouseover = function (e) {
                target = TrustpilotPreview.getTarget(TrustpilotPreview.getEvent(e));
                TrustpilotPreview.toggleOverlay(true, target.closest('div'));
            };
            iframeWin.onmouseout = function (e) {
                target = TrustpilotPreview.getTarget(TrustpilotPreview.getEvent(e));
                TrustpilotPreview.toggleOverlay(false, target.closest('div'));
            };
            iframeWin.onscroll = function () {
                if (target) {
                    TrustpilotPreview.toggleOverlay(false, target.closest('div'));
                    TrustpilotPreview.toggleOverlay(true, target.closest('div'));
                }
            }
            iframeWin.onclick = function (e) {
                const event = TrustpilotPreview.getEvent(e);
                target = TrustpilotPreview.getTarget(event);

                event.preventDefault();

                if (target.tagName !== 'HTML' && target.tagName !== 'BODY') {
                    const div = target.closest('div');
                    TrustpilotPreview.removeTrustBox(iframe.contentDocument);
                    let {xpath, error} = TrustpilotPreview.getXPath(div);
                    field.value = xpath;
                    TrustpilotPreview.renderTrustBox(field.value, iframe, error);
                }
            };
        }
        TrustpilotPreview.setVisibility();
        TrustpilotPreview.toggleSpinners(false);
    }

    static injectCss(doc, cssUrl) {
        const link = doc.createElement('link');
        link.setAttribute('type', 'text/css');
        link.setAttribute('rel', 'stylesheet');
        link.setAttribute('href', cssUrl);
        doc.head.appendChild(link);
    }

    static getIFrame() {
        return document.getElementById(TrustpilotElements.IFRAME_ID);
    }

    static getXPathField() {
        return document.getElementById(TrustpilotElements.XPATH_FIELD_ID);
    }

    static getTrustBoxCodeSnippetField() {
        return document.getElementById(TrustpilotElements.TRUSTBOX_CODE_SNIPPET_FIELD_ID);
    }

    static getTrustboxField() {
        return document.getElementById(TrustpilotElements.TRUSTBOX_FIELD_ID);
    }

    static getPositionField() {
        return document.getElementById(TrustpilotElements.POSITION_FIELD_ID);
    }

    static getPageField() {
        return document.getElementById(TrustpilotElements.PAGE_FIELD_ID);
    }

    static getXPathFieldInherited() {
        return (document.getElementById(TrustpilotElements.XPATH_FIELD_ID_INHERIT));
    }

    static getTrustBoxCodeSnippetFieldInherited() {
        return (document.getElementById(TrustpilotElements.TRUSTBOX_CODE_SNIPPET_FIELD_ID_INHERIT));
    }

    static getPositionFieldInherited() {
        return (document.getElementById(TrustpilotElements.POSITION_FIELD_ID_INHERIT));
    }

    static getPageFieldInherited() {
        return (document.getElementById(TrustpilotElements.PAGE_FIELD_ID_INHERIT));
    }

    static getElementByXPath(xpath, doc) {
        if (xpath && doc) {
            const evalRes = doc.evaluate(xpath, doc, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null);
            if (evalRes) {
                return evalRes.singleNodeValue;
            }
        }
        return null;
    }

    static getEvent(e) {
        if (e === undefined) e = window.event; // IE hack
        return e;
    }

    static getTarget(e) {
        return 'target' in e ? e.target : e.srcElement; // another IE hack
    }

    static removeTrustBox(doc) {
        const ids = ['trustpilot-widget-trustbox-preview', 'trustpilot-widget-trustbox'];
        ids.forEach(function (id) {
            const el = doc.getElementById(id);
            if (el) el.parentNode.removeChild(el);
        });
    }

    static getErrorBox(message) {
        const trustboxMessageBox = document.createElement('div');
        var code = `<div class="trustbox-message-box shake-and-hide-element">
                        <div class="fa fa-warning warning-icon"></div>
                        <div class="trustbox-message-text">${message}</div>
                    </div>`;
        trustboxMessageBox.insertAdjacentHTML("afterbegin", code);
        return trustboxMessageBox;
    }

    static renderTrustBox(xpath, iframe, error) {
        const tbcs = TrustpilotPreview.getTrustBoxCodeSnippetField();
        const pos = TrustpilotPreview.getPositionField();
        const trustboxParser = new DOMParser().parseFromString(tbcs.value, 'text/html');

        //we are only support single Element
        if (trustboxParser && trustboxParser.body && trustboxParser.body.childNodes.length > 0) {
            const trustboxWidget = trustboxParser.body.childNodes[0];

            const trustboxWrapper = document.createElement('div');
            trustboxWrapper.setAttribute('id', 'trustpilot-widget-trustbox-preview');
            trustboxWrapper.setAttribute('style', 'overflow: hidden; width: 100%;');         
            
            if (error) {
                const trustboxMessageBox = TrustpilotPreview.getErrorBox(error);
                trustboxWrapper.appendChild(trustboxMessageBox);
            }

            trustboxWrapper.appendChild(trustboxWidget);

            const container = TrustpilotPreview.getElementByXPath(xpath, iframe.contentDocument);
            if (container) {
                if (pos.value == 'before') {
                    container.parentNode.insertBefore(trustboxWrapper, container);
                } else {
                    container.parentNode.insertBefore(trustboxWrapper, container.nextSibling);
                }
                iframe.contentWindow.Trustpilot.Modules.WidgetManagement.findAndApplyWidgets();
            }
        }
    }

    static createOverlayDiv(el) {
        const rect = el.getBoundingClientRect();
        var div = document.createElement('div');
        div.addClassName('trustpilot-overlay');
        div.setAttribute("style", `left:${rect.left}px; top:${rect.top}px; width:${rect.width}px; height:${rect.height}px;`);
        return div;
    }

    static toggleOverlay(toggle, e) {
        if(e) {
            if (toggle) {
                const div = TrustpilotPreview.createOverlayDiv(e);
                e.appendChild(div);
                return;
            } else {
                let children = e.children;
                for (let i = 0; i < children.length; i++) {
                    if (children[i].className && children[i].className.split(' ').includes('trustpilot-overlay')) {
                        e.removeChild(children[i]);
                        return;
                    }
                }
            }
        }
        return;
    }

    static getXPath(el, isInForm = false) {
        let xpath;
        let error;
        if (el.tagName === 'FORM') {
            isInForm = true;
        }
        if (el.id) {
            xpath = 'id("' + el.id + '")';
            if (isInForm) {
                error = 'Your selection is in a form, so it might be not consistent through different pages.';
                return {xpath, error};
            }
            return {xpath, error};
        }
        if (el === document.body) {
            xpath = el.tagName;
            return {xpath, error};
        }

        let ix = 0;
        if (!el.parentNode) return {xpath, error};
        const siblings = el.parentNode.childNodes;
        for (let i = 0; i < siblings.length; i++) {
            const sibling = siblings[i];
            if (sibling === el) {
                let {xpath, error} = TrustpilotPreview.getXPath(el.parentNode, isInForm);
                xpath = (xpath ? xpath : '')  + '/' + el.tagName + '[' + (ix + 1) + ']';
                return {xpath, error};
            }
            if (sibling.nodeType === 1 && sibling.tagName === el.tagName)
                ix++;
        }
    }

    static bindEventHandler() {
        const pageField = TrustpilotPreview.getPageField();
        if (pageField)
            pageField.addEventListener('change', function () {
                const iframe = TrustpilotPreview.getIFrame();
                const field = TrustpilotPreview.getXPathField();
                if (iframe && field) {
                    field.value = '';
                    iframe.src = iframe.getAttribute('data-' + pageField.options[pageField.selectedIndex].value);
                }
            });
        const positionField = TrustpilotPreview.getPositionField();
        if (positionField)
            positionField.addEventListener('change', function () {
                const iframe = TrustpilotPreview.getIFrame();
                const field = TrustpilotPreview.getXPathField();
                if (iframe && field) {
                    TrustpilotPreview.removeTrustBox(iframe.contentDocument);
                    TrustpilotPreview.renderTrustBox(field.value, iframe);
                }
            });
        const trustboxField = TrustpilotPreview.getTrustboxField();
        if (trustboxField)
            trustboxField.addEventListener('change', function () {
                TrustpilotPreview.setVisibility();
            });
        const tbcs = TrustpilotPreview.getTrustBoxCodeSnippetField();
        if (tbcs)
            tbcs.addEventListener('input', function () {
                TrustpilotPreview.setVisibility();
            });
        const iframeInheritanceCheckbox = TrustpilotPreview.getXPathFieldInherited();
        if (iframeInheritanceCheckbox) {
            iframeInheritanceCheckbox.addEventListener('change', function () {
                TrustpilotPreview.setVisibility();
            });
        }

    }

    static setVisibility() {
        const trustboxField = TrustpilotPreview.getTrustboxField();
        const tbcs = TrustpilotPreview.getTrustBoxCodeSnippetField();
        const trustboxValue = trustboxField.options[trustboxField.selectedIndex].value;

        const pageFieldInherited = TrustpilotPreview.getPageFieldInherited();

        if (trustboxValue === 'disabled') {
            const iframe = TrustpilotPreview.getIFrame();
            TrustpilotPreview.removeTrustBox(iframe.contentDocument);
        }

        if (trustboxValue === 'disabled' || tbcs.value === '' || (pageFieldInherited && pageFieldInherited.checked)) {
            (TrustpilotPreview.getPageField()).disabled = 'disabled';
        } else {
            (TrustpilotPreview.getPageField()).removeAttribute('disabled');
            const iframe = TrustpilotPreview.getIFrame();
            const field = TrustpilotPreview.getXPathField();
            if (iframe && field) {
                TrustpilotPreview.removeTrustBox(iframe.contentDocument);
                TrustpilotPreview.renderTrustBox(field.value, iframe);
            }
        }

        const positionFieldInherited = TrustpilotPreview.getPositionFieldInherited();
        if (trustboxValue === 'disabled' || tbcs.value === '' || (positionFieldInherited && positionFieldInherited.checked)) {
            (TrustpilotPreview.getPositionField()).disabled = 'disabled';
        } else {
            (TrustpilotPreview.getPositionField()).removeAttribute('disabled');
        }

        const xPathFieldInherited = TrustpilotPreview.getXPathFieldInherited();
        if (trustboxValue === 'disabled' || tbcs.value === '' || (xPathFieldInherited && xPathFieldInherited.checked)) {
            (TrustpilotPreview.getIFrame()).style = 'pointer-events:none; opacity:0.5;';
        } else {
            (TrustpilotPreview.getIFrame()).style = 'pointer-events:auto; opacity:1;';
        }

        const tustBoxCodeSnippetFieldInherited = TrustpilotPreview.getTrustBoxCodeSnippetFieldInherited();
        if (trustboxValue === 'disabled' || (tustBoxCodeSnippetFieldInherited && tustBoxCodeSnippetFieldInherited.checked)) {
            (TrustpilotPreview.getTrustBoxCodeSnippetField()).disabled = 'disabled';
        } else {
            (TrustpilotPreview.getTrustBoxCodeSnippetField()).removeAttribute('disabled');
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    TrustpilotPreview.toggleSpinners(true);
    TrustpilotPreview.bindEventHandler();
});