/**
 * Maho
 *
 * @package    Maho_NetsEasy
 * @copyright  Copyright (c) 2026 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

(function () {
    'use strict';

    const container = document.getElementById('netseasy-checkout');
    if (!container) {
        return;
    }

    const paymentId = container.dataset.paymentId;
    const checkoutKey = container.dataset.checkoutKey;
    const returnUrl = container.dataset.returnUrl;
    const locale = container.dataset.locale || 'en-GB';
    const checkoutSrc = container.dataset.checkoutSrc || 'https://checkout.dibspayment.eu/v1/checkout.js';
    const msgProcessing = container.dataset.msgProcessing || 'Processing payment...';
    const msgMissingConfig = container.dataset.msgMissingConfig || 'Missing checkout configuration.';
    const msgLoadFailed = container.dataset.msgLoadFailed || 'Failed to load payment widget. Please refresh the page.';

    if (!paymentId || !checkoutKey || !returnUrl) {
        container.textContent = msgMissingConfig;
        return;
    }

    function initCheckout() {
        if (typeof Dibs === 'undefined' || !Dibs.Checkout) {
            container.textContent = msgLoadFailed;
            return;
        }

        const checkoutOptions = {
            checkoutKey: checkoutKey,
            paymentId: paymentId,
            containerId: 'netseasy-checkout',
            language: locale,
        };

        const checkout = new Dibs.Checkout(checkoutOptions);

        checkout.on('payment-completed', function () {
            container.innerHTML = '<p style="text-align:center;padding:2rem;">' + msgProcessing + '</p>';
            mahoFetch(returnUrl, { method: 'POST' })
                .then(function (data) {
                    window.location.href = data.redirect || returnUrl;
                })
                .catch(function () {
                    window.location.href = returnUrl;
                });
        });

        checkout.on('pay-initialized', function () {
            container.style.minHeight = '400px';
        });
    }

    // Load Nets Easy SDK
    const script = document.createElement('script');
    script.src = checkoutSrc;
    script.onload = initCheckout;
    script.onerror = function () {
        container.textContent = msgLoadFailed;
    };
    document.head.appendChild(script);
})();
