<?php

declare(strict_types=1);

/**
 * Maho
 *
 * @package    Maho_NetsEasy
 * @copyright  Copyright (c) 2026 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Maho_NetsEasy_Model_Api_Dto_PaymentResponse
{
    public function __construct(
        private readonly array $data,
    ) {}

    public function getPaymentId(): string
    {
        return (string) ($this->data['payment']['paymentId'] ?? $this->data['paymentId'] ?? '');
    }

    public function getHostedPaymentPageUrl(): ?string
    {
        return $this->data['hostedPaymentPageUrl'] ?? null;
    }

    public function getReservedAmount(): int
    {
        return (int) ($this->data['payment']['summary']['reservedAmount'] ?? 0);
    }

    public function getChargedAmount(): int
    {
        return (int) ($this->data['payment']['summary']['chargedAmount'] ?? 0);
    }

    public function getRefundedAmount(): int
    {
        return (int) ($this->data['payment']['summary']['refundedAmount'] ?? 0);
    }

    public function getCancelledAmount(): int
    {
        return (int) ($this->data['payment']['summary']['cancelledAmount'] ?? 0);
    }

    public function getOrderReference(): string
    {
        return (string) ($this->data['payment']['orderDetails']['reference'] ?? '');
    }

    public function getOrderAmount(): int
    {
        return (int) ($this->data['payment']['orderDetails']['amount'] ?? 0);
    }

    public function getCurrency(): string
    {
        return (string) ($this->data['payment']['orderDetails']['currency'] ?? '');
    }

    public function getCheckoutUrl(): ?string
    {
        return $this->data['payment']['checkout']['url'] ?? null;
    }

    /**
     * @return array<array{chargeId: string, amount: int, created: string}>
     */
    public function getCharges(): array
    {
        return $this->data['payment']['charges'] ?? [];
    }

    public function getFirstChargeId(): ?string
    {
        $charges = $this->getCharges();
        return $charges[0]['chargeId'] ?? null;
    }

    public function getConsumerEmail(): ?string
    {
        return $this->data['payment']['consumer']['privatePerson']['email']
            ?? $this->data['payment']['consumer']['company']['contactDetails']['email']
            ?? null;
    }

    public function getConsumerPhone(): ?string
    {
        $phone = $this->data['payment']['consumer']['privatePerson']['phoneNumber']
            ?? $this->data['payment']['consumer']['company']['contactDetails']['phoneNumber']
            ?? null;

        if (is_array($phone)) {
            return trim(($phone['prefix'] ?? '') . ($phone['number'] ?? ''));
        }

        return $phone;
    }

    public function getPaymentType(): ?string
    {
        return $this->data['payment']['paymentDetails']['paymentType'] ?? null;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->data['payment']['paymentDetails']['paymentMethod'] ?? null;
    }

    public function getMaskedPan(): ?string
    {
        return $this->data['payment']['paymentDetails']['cardDetails']['maskedPan'] ?? null;
    }

    public function getExpiryDate(): ?string
    {
        return $this->data['payment']['paymentDetails']['cardDetails']['expiryDate'] ?? null;
    }

    public function isReserved(): bool
    {
        return $this->getReservedAmount() > 0;
    }

    public function isCharged(): bool
    {
        return $this->getChargedAmount() > 0;
    }

    public function isFullyCharged(): bool
    {
        return $this->getReservedAmount() > 0
            && $this->getChargedAmount() >= $this->getReservedAmount();
    }

    public function isCancelled(): bool
    {
        return $this->getCancelledAmount() > 0;
    }

    public function getRawData(): array
    {
        return $this->data;
    }
}
