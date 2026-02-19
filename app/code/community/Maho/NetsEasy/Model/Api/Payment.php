<?php

declare(strict_types=1);

/**
 * Maho
 *
 * @package    Maho_NetsEasy
 * @copyright  Copyright (c) 2026 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Maho_NetsEasy_Model_Api_Payment
{
    private function getClient(): Maho_NetsEasy_Model_Api_Client
    {
        return Mage::getSingleton('netseasy/api_client');
    }

    /**
     * Validate that a Nets ID (payment or charge) contains only safe characters
     */
    private function validateNetsId(string $id, string $label): void
    {
        if (!preg_match('/^[a-f0-9-]+$/i', $id)) {
            Mage::throwException(Mage::helper('netseasy')->__('Invalid Nets Easy %s format.', $label));
        }
    }

    public function createPayment(
        Maho_NetsEasy_Model_Api_Dto_CreatePaymentRequest $request,
        ?int $storeId = null,
    ): Maho_NetsEasy_Model_Api_Dto_PaymentResponse {
        $response = $this->getClient()->post('/v1/payments', $request->toArray(), $storeId);
        return new Maho_NetsEasy_Model_Api_Dto_PaymentResponse($response);
    }

    public function getPayment(
        string $paymentId,
        ?int $storeId = null,
    ): Maho_NetsEasy_Model_Api_Dto_PaymentResponse {
        $this->validateNetsId($paymentId, 'payment ID');
        $response = $this->getClient()->get("/v1/payments/{$paymentId}", $storeId);
        return new Maho_NetsEasy_Model_Api_Dto_PaymentResponse($response);
    }

    public function chargePayment(
        string $paymentId,
        Maho_NetsEasy_Model_Api_Dto_ChargeRequest $request,
        ?int $storeId = null,
    ): Maho_NetsEasy_Model_Api_Dto_ChargeResponse {
        $this->validateNetsId($paymentId, 'payment ID');
        $response = $this->getClient()->post("/v1/payments/{$paymentId}/charges", $request->toArray(), $storeId);
        return new Maho_NetsEasy_Model_Api_Dto_ChargeResponse($response);
    }

    public function refundPayment(
        string $chargeId,
        Maho_NetsEasy_Model_Api_Dto_RefundRequest $request,
        ?int $storeId = null,
    ): Maho_NetsEasy_Model_Api_Dto_RefundResponse {
        $this->validateNetsId($chargeId, 'charge ID');
        $response = $this->getClient()->post("/v1/charges/{$chargeId}/refunds", $request->toArray(), $storeId);
        return new Maho_NetsEasy_Model_Api_Dto_RefundResponse($response);
    }

    public function cancelPayment(string $paymentId, ?int $storeId = null): void
    {
        $this->validateNetsId($paymentId, 'payment ID');
        $this->getClient()->post("/v1/payments/{$paymentId}/cancels", [], $storeId);
    }

    /**
     * Terminate a checkout session before the payment has been reserved.
     * Use this when a customer abandons checkout before completing payment.
     */
    public function terminatePayment(string $paymentId, ?int $storeId = null): void
    {
        $this->validateNetsId($paymentId, 'payment ID');
        $this->getClient()->put("/v1/payments/{$paymentId}/terminate", [], $storeId);
    }

    public function updateReference(string $paymentId, string $reference, ?int $storeId = null): void
    {
        $this->validateNetsId($paymentId, 'payment ID');
        $this->getClient()->put("/v1/payments/{$paymentId}/referenceinformation", [
            'reference' => $reference,
        ], $storeId);
    }
}
