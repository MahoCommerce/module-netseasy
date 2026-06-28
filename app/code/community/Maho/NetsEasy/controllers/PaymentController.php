<?php

/**
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package Maho_NetsEasy
 */

declare(strict_types=1);

class Maho_NetsEasy_PaymentController extends Mage_Core_Controller_Front_Action
{
    private function _getCheckoutSession(): Mage_Checkout_Model_Session
    {
        return Mage::getSingleton('checkout/session');
    }

    private function _getApiPayment(): Maho_NetsEasy_Model_Api_Payment
    {
        return Mage::getModel('netseasy/api_payment');
    }

    private function _getHelper(): Maho_NetsEasy_Helper_Data
    {
        return Mage::helper('netseasy');
    }

    /**
     * Hosted flow: redirect customer to Nets Easy hosted payment page
     */
    #[Maho\Config\Route('/netseasy/payment/redirect')]
    public function redirectAction(): void
    {
        $session = $this->_getCheckoutSession();
        $order = $session->getLastRealOrder();

        if (!$order->getId()) {
            $this->_redirect('checkout/cart');
            return;
        }

        $payment = $order->getPayment();
        $paymentId = $payment ? $payment->getAdditionalInformation('netseasy_payment_id') : null;
        if (!$paymentId) {
            $session->addError($this->_getHelper()->__('Payment session could not be initialized.'));
            $this->_redirect('checkout/cart');
            return;
        }

        try {
            $paymentResponse = $this->_getApiPayment()->getPayment($paymentId, (int) $order->getStoreId());
            $hostedUrl = $paymentResponse->getHostedPaymentPageUrl();

            if (!$hostedUrl) {
                Mage::throwException($this->_getHelper()->__('Could not retrieve hosted payment page URL.'));
            }

            $this->loadLayout();
            $block = $this->getLayout()->getBlock('netseasy.redirect');
            if ($block) {
                $block->setRedirectUrl($hostedUrl);
            }
            $this->renderLayout();
        } catch (Exception $e) {
            Mage::logException($e);
            $session->addError($this->_getHelper()->__('An error occurred during payment initialization.'));
            $this->_restoreQuoteAndRedirectToCart();
        }
    }

    /**
     * Embedded flow: render page with embedded checkout widget
     */
    #[Maho\Config\Route('/netseasy/payment/checkout')]
    public function checkoutAction(): void
    {
        $session = $this->_getCheckoutSession();
        $order = $session->getLastRealOrder();

        if (!$order->getId()) {
            $this->_redirect('checkout/cart');
            return;
        }

        $payment = $order->getPayment();
        $paymentId = $payment ? $payment->getAdditionalInformation('netseasy_payment_id') : null;
        $checkoutKey = $this->_getHelper()->getCheckoutKey((int) $order->getStoreId());

        if (!$paymentId || !$checkoutKey) {
            $session->addError($this->_getHelper()->__('Payment session could not be initialized.'));
            $this->_restoreQuoteAndRedirectToCart();
            return;
        }

        $this->loadLayout();
        $block = $this->getLayout()->getBlock('netseasy.checkout');
        if ($block) {
            $block->setPaymentId($paymentId);
            $block->setCheckoutKey($checkoutKey);
            $block->setReturnUrl(Mage::getUrl('netseasy/payment/return', ['_secure' => true]));
        }
        $this->renderLayout();
    }

    /**
     * Customer returns from payment (both hosted and embedded flows)
     *
     * Hosted flow: GET redirect from Nets payment page
     * Embedded flow: POST via mahoFetch() from checkout.js
     */
    #[Maho\Config\Route('/netseasy/payment/return')]
    public function returnAction(): void
    {
        $isAjax = $this->getRequest()->isPost();
        $session = $this->_getCheckoutSession();
        $order = $session->getLastRealOrder();

        if (!$order->getId()) {
            if ($isAjax) {
                $this->_sendJsonResponse(['redirect' => Mage::getUrl('checkout/cart')]);
                return;
            }
            $this->_redirect('checkout/cart');
            return;
        }

        $payment = $order->getPayment();
        $paymentId = $payment ? $payment->getAdditionalInformation('netseasy_payment_id') : null;
        if (!$paymentId) {
            if ($isAjax) {
                $this->_restoreQuoteAndSendJsonError();
                return;
            }
            $this->_restoreQuoteAndRedirectToCart();
            return;
        }

        try {
            $paymentResponse = $this->_getApiPayment()->getPayment($paymentId, (int) $order->getStoreId());

            if (($paymentResponse->isReserved() || $paymentResponse->isCharged()) && !$paymentResponse->isCancelled()) {
                $session->getQuote()->setIsActive(0)->save();
                $successUrl = Mage::getUrl('checkout/onepage/success', ['_secure' => true]);
                if ($isAjax) {
                    $this->_sendJsonResponse(['redirect' => $successUrl]);
                    return;
                }
                $this->_redirect('checkout/onepage/success', ['_secure' => true]);
                return;
            }

            $session->addError($this->_getHelper()->__('Payment was not completed. Please try again.'));
            if ($isAjax) {
                $this->_restoreQuoteAndSendJsonError();
                return;
            }
            $this->_restoreQuoteAndRedirectToCart();
        } catch (Exception $e) {
            Mage::logException($e);
            $session->addError($this->_getHelper()->__('Could not verify payment status. Please contact support.'));
            if ($isAjax) {
                $this->_restoreQuoteAndSendJsonError();
                return;
            }
            $this->_restoreQuoteAndRedirectToCart();
        }
    }

    /**
     * Customer cancelled payment
     */
    #[Maho\Config\Route('/netseasy/payment/cancel')]
    public function cancelAction(): void
    {
        $this->_getCheckoutSession()->addNotice(
            $this->_getHelper()->__('Payment was cancelled.'),
        );
        $this->_restoreQuoteAndRedirectToCart();
    }

    private function _sendJsonResponse(array $data): void
    {
        $this->getResponse()
            ->setHeader('Content-Type', 'application/json', true)
            ->setBody(Mage::helper('core')->jsonEncode($data));
    }

    private function _restoreQuoteAndSendJsonError(): void
    {
        $this->_restoreQuote();
        $this->_sendJsonResponse(['redirect' => Mage::getUrl('checkout/cart')]);
    }

    private function _restoreQuote(): void
    {
        $session = $this->_getCheckoutSession();
        $order = $session->getLastRealOrder();

        if ($order->getId()) {
            // Terminate the Nets Easy checkout session
            $payment = $order->getPayment();
            $paymentId = $payment ? $payment->getAdditionalInformation('netseasy_payment_id') : null;
            if ($paymentId) {
                try {
                    $this->_getApiPayment()->terminatePayment($paymentId, (int) $order->getStoreId());
                } catch (\Exception $e) {
                    // Terminate may fail if the payment was already reserved or completed
                    Mage::log(
                        sprintf('NetsEasy: Failed to terminate payment %s: %s', $paymentId, $e->getMessage()),
                        Mage::LOG_DEBUG,
                        'netseasy.log',
                    );
                }
            }

            if ($order->canCancel()) {
                $order->registerCancellation($this->_getHelper()->__('Payment cancelled by customer.'))->save();
            }
        }

        $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
        if ($quote->getId()) {
            $quote->setIsActive(1)
                ->setReservedOrderId('')
                ->save();
            $session->replaceQuote($quote)
                ->unsLastRealOrderId();
        }
    }

    private function _restoreQuoteAndRedirectToCart(): void
    {
        $this->_restoreQuote();
        $this->_redirect('checkout/cart');
    }
}
