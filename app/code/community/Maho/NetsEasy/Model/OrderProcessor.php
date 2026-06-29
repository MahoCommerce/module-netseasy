<?php

/**
 * Moves a Nets Easy order out of pending_payment once the payment is reserved or charged.
 *
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package Maho_NetsEasy
 */

declare(strict_types=1);

class Maho_NetsEasy_Model_OrderProcessor
{
    /**
     * Promote a pending-payment order to processing after Nets reserved or charged it.
     *
     * Idempotent: if the order has already left pending_payment (e.g. the webhook ran
     * first, or the customer reloaded the return URL) this is a no-op. That lets both the
     * synchronous return flow and the async webhook call it safely without double-invoicing.
     *
     * @return bool true when this call finalized the order
     */
    public function markReservedOrCharged(
        Mage_Sales_Model_Order $order,
        Maho_NetsEasy_Model_Api_Dto_PaymentResponse $paymentResponse,
        string $comment,
    ): bool {
        if ($order->getState() !== Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
            return false;
        }

        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $order->getPayment();
        $payment->setAdditionalInformation('netseasy_payment_type', $paymentResponse->getPaymentType());
        $payment->setAdditionalInformation('netseasy_payment_method', $paymentResponse->getPaymentMethod());

        if ($maskedPan = $paymentResponse->getMaskedPan()) {
            $payment->setAdditionalInformation('netseasy_masked_pan', $maskedPan);
        }

        $payment->setTransactionId($paymentResponse->getPaymentId());
        $payment->setIsTransactionClosed(false);

        $paymentAction = Mage::helper('netseasy')->getPaymentAction((int) $order->getStoreId());
        if ($paymentAction === Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE && $order->canInvoice()) {
            // Capture online: our payment model charges Nets, the invoice records the
            // capture, and the order moves to processing.
            $invoice = $order->prepareInvoice();
            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
            $invoice->register();

            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, $comment);

            Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($order)
                ->save();
        } else {
            // Authorize only: record the authorization transaction (this also promotes
            // the order to processing). A bare setState() would leave the Transactions
            // grid empty and give admin nothing to void/refund against.
            $payment->registerAuthorizationNotification((float) $order->getBaseGrandTotal());
            $order->addStatusHistoryComment($comment);
            $order->save();
        }

        return true;
    }
}
