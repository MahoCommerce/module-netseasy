<?php

declare(strict_types=1);

/**
 * Maho
 *
 * @package    Maho_NetsEasy
 * @copyright  Copyright (c) 2026 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Maho_NetsEasy_Model_Payment extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'netseasy';

    protected $_formBlockType = 'netseasy/form';
    protected $_infoBlockType = 'netseasy/info';

    protected $_isInitializeNeeded      = true;
    protected $_isGateway               = true;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = true;
    protected $_canRefund               = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid                 = true;
    protected $_canUseInternal          = false;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = false;
    protected $_canFetchTransactionInfo = true;
    protected $_canManageRecurringProfiles = false;

    private function getHelper(): Maho_NetsEasy_Helper_Data
    {
        return Mage::helper('netseasy');
    }

    private function getApiPayment(): Maho_NetsEasy_Model_Api_Payment
    {
        return Mage::getModel('netseasy/api_payment');
    }

    private function getLocale(): Maho_NetsEasy_Model_Locale
    {
        return Mage::getSingleton('netseasy/locale');
    }

    /**
     * Validate that the order currency is supported by Nets Easy
     */
    #[\Override]
    public function canUseForCurrency($currencyCode): bool
    {
        return $this->getLocale()->isCurrencySupported($currencyCode);
    }

    /**
     * Set pending_payment state, create Nets payment session, store paymentId
     */
    #[\Override]
    public function initialize($paymentAction, $stateObject): self
    {
        $stateObject->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);

        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        $helper = $this->getHelper();
        $storeId = (int) $order->getStoreId();

        $request = $this->buildCreatePaymentRequest($order, $storeId);

        $response = $this->getApiPayment()->createPayment($request, $storeId);
        $paymentId = $response->getPaymentId();

        $payment->setAdditionalInformation('netseasy_payment_id', $paymentId);
        $payment->setAdditionalInformation('netseasy_checkout_flow', $helper->getCheckoutFlow($storeId));

        if ($response->getHostedPaymentPageUrl()) {
            $payment->setAdditionalInformation('netseasy_hosted_payment_url', $response->getHostedPaymentPageUrl());
        }

        $this->savePaymentRecord($paymentId, (int) $order->getQuoteId(), $helper->getCheckoutFlow($storeId));

        return $this;
    }

    public function getOrderPlaceRedirectUrl(): string
    {
        $flow = $this->getInfoInstance()->getAdditionalInformation('netseasy_checkout_flow')
            ?: $this->getHelper()->getCheckoutFlow();

        if ($flow === 'hosted') {
            return Mage::getUrl('netseasy/payment/redirect', ['_secure' => true]);
        }

        return Mage::getUrl('netseasy/payment/checkout', ['_secure' => true]);
    }

    #[\Override]
    public function capture(\Maho\DataObject $payment, $amount): self
    {
        /** @var Mage_Sales_Model_Order_Payment $payment */
        $order = $payment->getOrder();
        $paymentId = $payment->getAdditionalInformation('netseasy_payment_id');

        if (!$paymentId) {
            Mage::throwException(Mage::helper('netseasy')->__('Cannot capture: Nets payment ID not found.'));
        }

        $amountMinor = Maho_NetsEasy_Model_Api_Dto_OrderItem::toMinorUnits((float) $amount);

        $chargeRequest = new Maho_NetsEasy_Model_Api_Dto_ChargeRequest($amountMinor);

        if ($this->getHelper()->shouldSendOrderItems((int) $order->getStoreId())) {
            $items = $this->buildOrderItemsFromInvoice($payment);
            $chargeRequest->setOrderItems($items);
        }

        $response = $this->getApiPayment()->chargePayment(
            $paymentId,
            $chargeRequest,
            (int) $order->getStoreId(),
        );

        $chargeId = $response->getChargeId();
        $payment->setTransactionId($chargeId);
        $payment->setIsTransactionClosed(false);
        $payment->setAdditionalInformation('netseasy_last_charge_id', $chargeId);

        return $this;
    }

    #[\Override]
    public function refund(\Maho\DataObject $payment, $amount): self
    {
        $order = $payment->getOrder();
        $chargeId = $this->getChargeIdForRefund($payment);

        if (!$chargeId) {
            Mage::throwException(Mage::helper('netseasy')->__('Cannot refund: no charge ID found.'));
        }

        $amountMinor = Maho_NetsEasy_Model_Api_Dto_OrderItem::toMinorUnits((float) $amount);

        $refundRequest = new Maho_NetsEasy_Model_Api_Dto_RefundRequest($amountMinor);

        if ($this->getHelper()->shouldSendOrderItems((int) $order->getStoreId())) {
            $items = $this->buildOrderItemsFromCreditmemo($payment);
            $refundRequest->setOrderItems($items);
        }

        $response = $this->getApiPayment()->refundPayment(
            $chargeId,
            $refundRequest,
            (int) $order->getStoreId(),
        );

        $refundId = $response->getRefundId();
        $payment->setTransactionId($refundId);
        $payment->setIsTransactionClosed(true);

        return $this;
    }

    #[\Override]
    public function void(\Maho\DataObject $payment): self
    {
        $paymentId = $payment->getAdditionalInformation('netseasy_payment_id');

        if (!$paymentId) {
            Mage::throwException(Mage::helper('netseasy')->__('Cannot void: Nets payment ID not found.'));
        }

        $order = $payment->getOrder();

        $this->getApiPayment()->cancelPayment($paymentId, (int) $order->getStoreId());

        $payment->setTransactionId($paymentId . '-void');
        $payment->setIsTransactionClosed(true);

        return $this;
    }

    #[\Override]
    public function cancel(\Maho\DataObject $payment): self
    {
        return $this->void($payment);
    }

    #[\Override]
    public function fetchTransactionInfo(Mage_Payment_Model_Info $payment, $transactionId): array
    {
        $paymentId = $payment->getAdditionalInformation('netseasy_payment_id');

        if (!$paymentId) {
            return [];
        }

        $order = $payment->getOrder();
        $response = $this->getApiPayment()->getPayment($paymentId, (int) $order->getStoreId());

        return [
            'netseasy_payment_id' => $response->getPaymentId(),
            'reserved_amount' => $response->getReservedAmount(),
            'charged_amount' => $response->getChargedAmount(),
            'refunded_amount' => $response->getRefundedAmount(),
            'cancelled_amount' => $response->getCancelledAmount(),
            'payment_type' => $response->getPaymentType(),
            'payment_method' => $response->getPaymentMethod(),
        ];
    }

    private function buildCreatePaymentRequest(
        Mage_Sales_Model_Order $order,
        int $storeId,
    ): Maho_NetsEasy_Model_Api_Dto_CreatePaymentRequest {
        $helper = $this->getHelper();
        $checkoutFlow = $helper->getCheckoutFlow($storeId);

        $integrationType = $checkoutFlow === 'hosted'
            ? 'HostedPaymentPage'
            : 'EmbeddedCheckout';

        $grandTotal = Maho_NetsEasy_Model_Api_Dto_OrderItem::toMinorUnits(
            (float) $order->getBaseGrandTotal(),
        );

        $request = new Maho_NetsEasy_Model_Api_Dto_CreatePaymentRequest(
            amount: $grandTotal,
            currency: $order->getBaseCurrencyCode(),
            reference: $order->getIncrementId(),
            integrationType: $integrationType,
            termsUrl: $helper->getTermsUrl($storeId),
            merchantHandlesConsumerData: $helper->getMerchantHandlesConsumerData($storeId),
            checkoutUrl: Mage::getUrl('netseasy/payment/checkout', ['_secure' => true]),
            returnUrl: Mage::getUrl('netseasy/payment/return', ['_secure' => true]),
            cancelUrl: Mage::getUrl('netseasy/payment/cancel', ['_secure' => true]),
        );

        if ($helper->shouldSendOrderItems($storeId)) {
            $items = $this->buildOrderItemsFromOrder($order);
            $request->setItems($items);
        }

        if ($helper->getMerchantHandlesConsumerData($storeId)) {
            $this->setConsumerData($request, $order);
        }

        $webhookSecret = $helper->getWebhookSecret($storeId);
        if ($webhookSecret) {
            $request->addWebhook(
                'payment.checkout.completed',
                Mage::getUrl('netseasy/webhook/checkoutCompleted', ['_secure' => true]),
                $webhookSecret,
            );
            $request->addWebhook(
                'payment.charge.created.v2',
                Mage::getUrl('netseasy/webhook/chargeCreated', ['_secure' => true]),
                $webhookSecret,
            );
            $request->addWebhook(
                'payment.refund.completed',
                Mage::getUrl('netseasy/webhook/refundCompleted', ['_secure' => true]),
                $webhookSecret,
            );
            $request->addWebhook(
                'payment.cancel.created',
                Mage::getUrl('netseasy/webhook/cancelCreated', ['_secure' => true]),
                $webhookSecret,
            );
            $request->addWebhook(
                'payment.reservation.created.v2',
                Mage::getUrl('netseasy/webhook/reservationCreated', ['_secure' => true]),
                $webhookSecret,
            );
        }

        return $request;
    }

    /**
     * @return Maho_NetsEasy_Model_Api_Dto_OrderItem[]
     */
    private function buildOrderItemsFromOrder(Mage_Sales_Model_Order $order): array
    {
        $items = [];

        foreach ($order->getAllVisibleItems() as $item) {
            $items[] = Maho_NetsEasy_Model_Api_Dto_OrderItem::fromOrderItem($item);
        }

        $shippingAmount = (float) $order->getBaseShippingAmount();
        if ($shippingAmount > 0) {
            $items[] = Maho_NetsEasy_Model_Api_Dto_OrderItem::fromShipping(
                $shippingAmount,
                (float) $order->getBaseShippingTaxAmount(),
                $this->calculateShippingTaxPercent($order),
            );
        }

        $discount = (float) $order->getBaseDiscountAmount();
        if ($discount < 0) {
            $items[] = Maho_NetsEasy_Model_Api_Dto_OrderItem::fromDiscount($discount);
        }

        return $items;
    }

    /**
     * @return Maho_NetsEasy_Model_Api_Dto_OrderItem[]
     */
    private function buildOrderItemsFromInvoice(\Maho\DataObject $payment): array
    {
        $invoice = $payment->getInvoice();

        if (!$invoice) {
            return $this->buildOrderItemsFromOrder($payment->getOrder());
        }

        return $this->buildOrderItemsFromDocument($invoice, $payment->getOrder());
    }

    /**
     * @return Maho_NetsEasy_Model_Api_Dto_OrderItem[]
     */
    private function buildOrderItemsFromCreditmemo(\Maho\DataObject $payment): array
    {
        $creditmemo = $payment->getCreditmemo();

        if (!$creditmemo) {
            return [];
        }

        return $this->buildOrderItemsFromDocument($creditmemo, $payment->getOrder());
    }

    /**
     * Build order items from an invoice or credit memo document
     *
     * @param Mage_Sales_Model_Order_Invoice|Mage_Sales_Model_Order_Creditmemo $document
     * @return Maho_NetsEasy_Model_Api_Dto_OrderItem[]
     */
    private function buildOrderItemsFromDocument($document, Mage_Sales_Model_Order $order): array
    {
        $items = [];

        foreach ($document->getAllItems() as $item) {
            if ($item->getQty() <= 0) {
                continue;
            }
            $orderItem = $item->getOrderItem();
            if ($orderItem->getParentItemId()) {
                continue;
            }
            $qty = (int) $item->getQty();
            $unitPrice = Maho_NetsEasy_Model_Api_Dto_OrderItem::toMinorUnits((float) $orderItem->getBasePrice());
            $taxPercent = (float) $orderItem->getTaxPercent();
            $taxRate = (int) round($taxPercent * 100);
            $netTotal = $unitPrice * $qty;
            $taxAmount = Maho_NetsEasy_Model_Api_Dto_OrderItem::toMinorUnits((float) $item->getBaseTaxAmount());
            $grossTotal = $netTotal + $taxAmount;

            $items[] = new Maho_NetsEasy_Model_Api_Dto_OrderItem(
                reference: (string) ($orderItem->getSku() ?: $orderItem->getProductId()),
                name: mb_substr(trim((string) $orderItem->getName()), 0, 128),
                quantity: $qty,
                unit: 'pcs',
                unitPrice: $unitPrice,
                taxRate: $taxRate,
                taxAmount: $taxAmount,
                grossTotalAmount: $grossTotal,
                netTotalAmount: $netTotal,
            );
        }

        $shippingAmount = (float) $document->getBaseShippingAmount();
        if ($shippingAmount > 0) {
            $items[] = Maho_NetsEasy_Model_Api_Dto_OrderItem::fromShipping(
                $shippingAmount,
                (float) $document->getBaseShippingTaxAmount(),
                $this->calculateShippingTaxPercent($order),
            );
        }

        return $items;
    }

    private function setConsumerData(
        Maho_NetsEasy_Model_Api_Dto_CreatePaymentRequest $request,
        Mage_Sales_Model_Order $order,
    ): void {
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();
        $email = $order->getCustomerEmail();
        $locale = $this->getLocale();

        if (!$email) {
            return;
        }

        $shipping = null;
        if ($shippingAddress) {
            $shipping = [
                'addressLine1' => (string) $shippingAddress->getStreet1(),
                'addressLine2' => (string) $shippingAddress->getStreet2(),
                'postalCode' => (string) $shippingAddress->getPostcode(),
                'city' => (string) $shippingAddress->getCity(),
                'country' => $locale->countryIso2ToIso3((string) $shippingAddress->getCountryId()),
            ];
        }

        $phone = null;
        $telephone = (string) ($billingAddress ? $billingAddress->getTelephone() : '');
        if ($telephone) {
            $countryId = $billingAddress ? (string) $billingAddress->getCountryId() : '';
            $dialCode = $this->getDialCodeByCountry($countryId);
            $phone = [
                'prefix' => $dialCode,
                'number' => $telephone,
            ];
        }

        // Detect B2B: if company name is set on the billing address
        $companyName = $billingAddress ? (string) $billingAddress->getCompany() : '';
        if ($companyName !== '') {
            $request->setCompanyConsumer(
                $email,
                $companyName,
                $shipping,
                $phone,
            );
        } else {
            $firstName = $billingAddress ? (string) $billingAddress->getFirstname() : '';
            $lastName = $billingAddress ? (string) $billingAddress->getLastname() : '';
            $request->setPrivateConsumer(
                $email,
                $firstName,
                $lastName,
                $shipping,
                $phone,
            );
        }
    }

    private function getDialCodeByCountry(string $countryId): string
    {
        $dialCodes = [
            'AT' => '+43', 'BE' => '+32', 'CH' => '+41', 'DE' => '+49',
            'DK' => '+45', 'ES' => '+34', 'FI' => '+358', 'FR' => '+33',
            'GB' => '+44', 'IE' => '+353', 'IT' => '+39', 'NL' => '+31',
            'NO' => '+47', 'PL' => '+48', 'SE' => '+46', 'US' => '+1',
        ];

        return $dialCodes[strtoupper($countryId)] ?? '+00';
    }

    private function calculateShippingTaxPercent(Mage_Sales_Model_Order $order): float
    {
        $shippingAmount = (float) $order->getBaseShippingAmount();
        if ($shippingAmount <= 0) {
            return 0.0;
        }

        $shippingTax = (float) $order->getBaseShippingTaxAmount();
        return ($shippingTax / $shippingAmount) * 100;
    }

    private function getChargeIdForRefund(\Maho\DataObject $payment): ?string
    {
        $chargeId = $payment->getAdditionalInformation('netseasy_last_charge_id');
        if ($chargeId) {
            return $chargeId;
        }

        $paymentId = $payment->getAdditionalInformation('netseasy_payment_id');
        if (!$paymentId) {
            return null;
        }

        $order = $payment->getOrder();
        $response = $this->getApiPayment()->getPayment($paymentId, (int) $order->getStoreId());
        return $response->getFirstChargeId();
    }

    private function savePaymentRecord(string $paymentId, int $quoteId, string $checkoutFlow): void
    {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $table = Mage::getSingleton('core/resource')->getTableName('netseasy/payment');

        $connection->insert($table, [
            'payment_id' => $paymentId,
            'quote_id' => $quoteId,
            'checkout_flow' => $checkoutFlow,
            'created_at' => Mage_Core_Model_Locale::now(),
        ]);
    }
}
