<?php

declare(strict_types=1);

/**
 * Maho
 *
 * @package    Maho_NetsEasy
 * @copyright  Copyright (c) 2026 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Maho_NetsEasy_WebhookController extends Mage_Core_Controller_Front_Action
{
    #[\Override]
    public function preDispatch(): static
    {
        // Skip session initialization for webhook endpoints
        $this->setFlag('', self::FLAG_NO_START_SESSION, true);

        parent::preDispatch();

        return $this;
    }

    /**
     * Validate webhook authorization header against configured secret
     */
    private function _validateWebhookAuth(?int $storeId = null): bool
    {
        $authHeader = $this->getRequest()->getHeader('Authorization');
        $webhookSecret = Mage::helper('netseasy')->getWebhookSecret($storeId);

        if (!$webhookSecret || !$authHeader) {
            return false;
        }

        return hash_equals($webhookSecret, $authHeader);
    }

    /**
     * Parse JSON request body
     */
    private function _getRequestBody(): ?array
    {
        $body = file_get_contents('php://input');
        if (!$body) {
            return null;
        }

        try {
            return Mage::helper('core')->jsonDecode($body);
        } catch (\JsonException $e) {
            Mage::logException($e);
            return null;
        }
    }

    /**
     * Send JSON response
     */
    private function _sendJsonResponse(int $httpCode, array $data): void
    {
        $this->getResponse()
            ->setHttpResponseCode($httpCode)
            ->setHeader('Content-Type', 'application/json', true)
            ->setBody(Mage::helper('core')->jsonEncode($data));
    }

    /**
     * Parse webhook body, resolve store context, and validate authorization
     *
     * Returns the parsed webhook data or null if validation failed (response already sent).
     * Auth is validated against the store-specific webhook secret, resolved via the
     * payment tracking table, to support multi-store setups with different secrets.
     */
    private function _processWebhook(): ?array
    {
        $data = $this->_getRequestBody();
        if (!$data) {
            $this->_sendJsonResponse(400, ['error' => 'Invalid request body']);
            return null;
        }

        $paymentId = $data['data']['paymentId'] ?? null;
        if (!$paymentId || !preg_match('/^[a-f0-9-]+$/i', (string) $paymentId)) {
            $this->_sendJsonResponse(400, ['error' => 'Invalid or missing paymentId']);
            return null;
        }

        $storeId = $this->_getStoreIdByPaymentId($paymentId);
        if (!$this->_validateWebhookAuth($storeId)) {
            $this->_sendJsonResponse(401, ['error' => 'Unauthorized']);
            return null;
        }

        return $data;
    }

    /**
     * Resolve store ID for a Nets payment by joining the tracking table with the quote
     */
    private function _getStoreIdByPaymentId(string $paymentId): ?int
    {
        $resource = Mage::getSingleton('core/resource');
        $adapter = $resource->getConnection('core_read');
        $select = $adapter->select()
            ->from(
                ['np' => $resource->getTableName('netseasy/payment')],
                [],
            )
            ->joinLeft(
                ['q' => $resource->getTableName('sales/quote')],
                'np.quote_id = q.entity_id',
                ['store_id'],
            )
            ->where('np.payment_id = ?', $paymentId)
            ->limit(1);

        $storeId = $adapter->fetchOne($select);
        return $storeId !== false ? (int) $storeId : null;
    }

    /**
     * Find order by Nets Easy payment ID from netseasy_payment tracking table
     *
     * Returns null if the payment record does not exist or the order is not yet linked.
     * The order_id column is nullable and is populated by the sales_order_place_after
     * observer, so webhooks that arrive before order placement will get null here.
     */
    private function _loadOrderByPaymentId(string $paymentId): ?Mage_Sales_Model_Order
    {
        $resource = Mage::getSingleton('core/resource');
        $adapter = $resource->getConnection('core_read');
        $select = $adapter->select()
            ->from($resource->getTableName('netseasy/payment'), ['order_id'])
            ->where('payment_id = ?', $paymentId)
            ->limit(1);

        $row = $adapter->fetchRow($select);
        if (!$row) {
            return null;
        }

        $orderId = $row['order_id'] ?? null;
        if (!$orderId) {
            return null;
        }

        $order = Mage::getModel('sales/order')->load($orderId);
        return $order->getId() ? $order : null;
    }

    /**
     * Handle payment.checkout.completed webhook
     */
    public function checkoutCompletedAction(): void
    {
        $data = $this->_processWebhook();
        if ($data === null) {
            return;
        }

        $paymentId = $data['data']['paymentId'];

        try {
            $order = $this->_loadOrderByPaymentId($paymentId);
            if (!$order) {
                // Order may not exist yet if webhook arrives before order placement completes
                Mage::log(
                    sprintf('NetsEasy checkout completed for payment %s but no order found yet', $paymentId),
                    Mage::LOG_INFO,
                    'netseasy.log',
                );
                $this->_sendJsonResponse(200, ['success' => true, 'note' => 'Order not yet created']);
                return;
            }

            // Verify payment via API
            $apiPayment = Mage::getModel('netseasy/api_payment');
            $paymentResponse = $apiPayment->getPayment($paymentId, (int) $order->getStoreId());

            if ($paymentResponse->isCancelled() || (!$paymentResponse->isReserved() && !$paymentResponse->isCharged())) {
                $this->_sendJsonResponse(400, ['error' => 'Payment not reserved or charged']);
                return;
            }

            $payment = $order->getPayment();
            $payment->setAdditionalInformation('netseasy_payment_type', $paymentResponse->getPaymentType());
            $payment->setAdditionalInformation('netseasy_payment_method', $paymentResponse->getPaymentMethod());

            if ($maskedPan = $paymentResponse->getMaskedPan()) {
                $payment->setAdditionalInformation('netseasy_masked_pan', $maskedPan);
            }

            $payment->setTransactionId($paymentId);
            $payment->setIsTransactionClosed(false);

            // Auto-capture: create invoice if payment action is authorize_capture
            $paymentAction = Mage::helper('netseasy')->getPaymentAction((int) $order->getStoreId());
            if ($paymentAction === 'authorize_capture' && $order->canInvoice()) {
                $invoice = $order->prepareInvoice();
                $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
                $invoice->register();

                $order->setState(
                    Mage_Sales_Model_Order::STATE_PROCESSING,
                    true,
                    Mage::helper('netseasy')->__('Payment confirmed by Nets Easy webhook.'),
                );

                Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($order)
                    ->save();
            } else {
                $order->setState(
                    Mage_Sales_Model_Order::STATE_PROCESSING,
                    true,
                    Mage::helper('netseasy')->__('Payment confirmed by Nets Easy webhook.'),
                );
                $order->save();
            }

            $this->_sendJsonResponse(200, ['success' => true]);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_sendJsonResponse(500, ['error' => 'Internal server error']);
        }
    }

    /**
     * Handle payment.charge.created.v2 webhook
     */
    public function chargeCreatedAction(): void
    {
        $data = $this->_processWebhook();
        if ($data === null) {
            return;
        }

        $paymentId = $data['data']['paymentId'];
        $chargeId = $data['data']['chargeId'] ?? null;

        try {
            $order = $this->_loadOrderByPaymentId($paymentId);
            if (!$order) {
                $this->_sendJsonResponse(404, ['error' => 'Order not found']);
                return;
            }

            if ($chargeId) {
                $order->getPayment()
                    ->setAdditionalInformation('netseasy_last_charge_id', $chargeId)
                    ->save();
            }

            $order->addStatusHistoryComment(
                Mage::helper('netseasy')->__('Nets Easy charge created: %s', $chargeId ?? 'unknown'),
            )->save();

            Mage::log(
                sprintf('NetsEasy charge created - Order: %s, Charge: %s', $order->getIncrementId(), $chargeId),
                Mage::LOG_INFO,
                'netseasy.log',
            );

            $this->_sendJsonResponse(200, ['success' => true]);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_sendJsonResponse(500, ['error' => 'Internal server error']);
        }
    }

    /**
     * Handle payment.refund.completed webhook
     */
    public function refundCompletedAction(): void
    {
        $data = $this->_processWebhook();
        if ($data === null) {
            return;
        }

        $paymentId = $data['data']['paymentId'];

        try {
            $order = $this->_loadOrderByPaymentId($paymentId);
            if (!$order) {
                $this->_sendJsonResponse(404, ['error' => 'Order not found']);
                return;
            }

            $refundId = $data['data']['refundId'] ?? 'unknown';
            $order->addStatusHistoryComment(
                Mage::helper('netseasy')->__('Nets Easy refund completed: %s', $refundId),
            )->save();

            Mage::log(
                sprintf('NetsEasy refund completed - Order: %s, Refund: %s', $order->getIncrementId(), $refundId),
                Mage::LOG_INFO,
                'netseasy.log',
            );

            $this->_sendJsonResponse(200, ['success' => true]);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_sendJsonResponse(500, ['error' => 'Internal server error']);
        }
    }

    /**
     * Handle payment.reservation.created.v2 webhook
     * Confirms that a payment has been reserved (authorized)
     */
    public function reservationCreatedAction(): void
    {
        $data = $this->_processWebhook();
        if ($data === null) {
            return;
        }

        $paymentId = $data['data']['paymentId'];

        try {
            $order = $this->_loadOrderByPaymentId($paymentId);
            if (!$order) {
                // Order may not exist yet if webhook arrives before order placement completes
                Mage::log(
                    sprintf('NetsEasy reservation created for payment %s but no order found yet', $paymentId),
                    Mage::LOG_INFO,
                    'netseasy.log',
                );
                $this->_sendJsonResponse(200, ['success' => true, 'note' => 'Order not yet created']);
                return;
            }

            $amount = $data['data']['amount']['amount'] ?? null;
            $currency = $data['data']['amount']['currency'] ?? null;

            $order->addStatusHistoryComment(
                Mage::helper('netseasy')->__(
                    'Nets Easy reservation created: %s %s',
                    $amount ? number_format((int) $amount / 100, 2) : 'unknown',
                    $currency ?? '',
                ),
            )->save();

            Mage::log(
                sprintf('NetsEasy reservation created - Order: %s, Payment: %s', $order->getIncrementId(), $paymentId),
                Mage::LOG_INFO,
                'netseasy.log',
            );

            $this->_sendJsonResponse(200, ['success' => true]);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_sendJsonResponse(500, ['error' => 'Internal server error']);
        }
    }

    /**
     * Handle payment.cancel.created webhook
     */
    public function cancelCreatedAction(): void
    {
        $data = $this->_processWebhook();
        if ($data === null) {
            return;
        }

        $paymentId = $data['data']['paymentId'];

        try {
            $order = $this->_loadOrderByPaymentId($paymentId);
            if (!$order) {
                $this->_sendJsonResponse(404, ['error' => 'Order not found']);
                return;
            }

            if ($order->canCancel()) {
                $order->registerCancellation(
                    Mage::helper('netseasy')->__('Payment cancelled via Nets Easy webhook.'),
                )->save();
            }

            Mage::log(
                sprintf('NetsEasy cancel created - Order: %s', $order->getIncrementId()),
                Mage::LOG_INFO,
                'netseasy.log',
            );

            $this->_sendJsonResponse(200, ['success' => true]);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_sendJsonResponse(500, ['error' => 'Internal server error']);
        }
    }
}
