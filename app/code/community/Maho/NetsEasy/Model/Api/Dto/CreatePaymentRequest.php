<?php

/**
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package Maho_NetsEasy
 */

declare(strict_types=1);

class Maho_NetsEasy_Model_Api_Dto_CreatePaymentRequest extends Maho_NetsEasy_Model_Api_Dto_AbstractRequest
{
    /** @var Maho_NetsEasy_Model_Api_Dto_OrderItem[] */
    private array $items = [];

    /** @var array<array{eventName: string, url: string, authorization: string}> */
    private array $webhooks = [];

    private ?array $consumer = null;

    public function __construct(
        private readonly int $amount,
        private readonly string $currency,
        private readonly string $reference,
        private readonly string $integrationType,
        private readonly string $termsUrl,
        private readonly bool $merchantHandlesConsumerData = true,
        private readonly ?string $checkoutUrl = null,
        private readonly ?string $returnUrl = null,
        private readonly ?string $cancelUrl = null,
    ) {}

    /**
     * @param Maho_NetsEasy_Model_Api_Dto_OrderItem[] $items
     */
    public function setItems(array $items): self
    {
        $this->items = $items;
        return $this;
    }

    public function addItem(Maho_NetsEasy_Model_Api_Dto_OrderItem $item): self
    {
        $this->items[] = $item;
        return $this;
    }

    public function addWebhook(string $eventName, string $url, #[\SensitiveParameter] string $authorization): self
    {
        $this->webhooks[] = [
            'eventName' => $eventName,
            'url' => $url,
            'authorization' => $authorization,
        ];
        return $this;
    }

    /**
     * Set consumer data for B2C (private person) checkout
     */
    public function setPrivateConsumer(
        #[\SensitiveParameter]
        string $email,
        string $firstName,
        string $lastName,
        ?array $shippingAddress = null,
        ?array $phoneNumber = null,
    ): self {
        // Nets expects email, phoneNumber and shippingAddress at the consumer top
        // level; privatePerson carries only the name. Nesting them inside
        // privatePerson makes Nets silently drop them, leaving the session without
        // a consumer email so the embedded checkout cannot complete the payment.
        $this->consumer = [
            'email' => $email,
            'privatePerson' => [
                'firstName' => $firstName,
                'lastName' => $lastName,
            ],
        ];

        if ($shippingAddress !== null) {
            $this->consumer['shippingAddress'] = $shippingAddress;
        }

        if ($phoneNumber !== null) {
            $this->consumer['phoneNumber'] = $phoneNumber;
        }

        return $this;
    }

    /**
     * Set consumer data for B2B (company) checkout
     */
    public function setCompanyConsumer(
        #[\SensitiveParameter]
        string $contactEmail,
        string $companyName,
        string $contactFirstName,
        string $contactLastName,
        ?array $shippingAddress = null,
        ?array $phoneNumber = null,
    ): self {
        // As with privatePerson, email/phoneNumber/shippingAddress live at the
        // consumer top level. The company object holds only name and contact.
        $this->consumer = [
            'email' => $contactEmail,
            'company' => [
                'name' => $companyName,
                'contact' => [
                    'firstName' => $contactFirstName,
                    'lastName' => $contactLastName,
                ],
            ],
        ];

        if ($shippingAddress !== null) {
            $this->consumer['shippingAddress'] = $shippingAddress;
        }

        if ($phoneNumber !== null) {
            $this->consumer['phoneNumber'] = $phoneNumber;
        }

        return $this;
    }

    #[\Override]
    public function toArray(): array
    {
        $items = array_map(
            static fn(Maho_NetsEasy_Model_Api_Dto_OrderItem $item) => $item->toArray(),
            $this->items,
        );

        $checkout = [
            'integrationType' => $this->integrationType,
            'termsUrl' => $this->termsUrl,
            'merchantHandlesConsumerData' => $this->merchantHandlesConsumerData,
        ];

        if ($this->integrationType === 'EmbeddedCheckout') {
            if ($this->checkoutUrl !== null) {
                $checkout['url'] = $this->checkoutUrl;
            }
        }

        if ($this->returnUrl !== null) {
            $checkout['returnUrl'] = $this->returnUrl;
        }

        // Nets only accepts cancelUrl for the hosted payment page; it is rejected for EmbeddedCheckout.
        if ($this->integrationType === 'HostedPaymentPage' && $this->cancelUrl !== null) {
            $checkout['cancelUrl'] = $this->cancelUrl;
        }

        if ($this->consumer !== null) {
            $checkout['consumer'] = $this->consumer;
        }

        $data = [
            'order' => [
                'items' => $items,
                'amount' => $this->amount,
                'currency' => $this->currency,
                'reference' => $this->reference,
            ],
            'checkout' => $checkout,
        ];

        if (!empty($this->webhooks)) {
            $data['notifications'] = [
                'webhooks' => $this->webhooks,
            ];
        }

        return $data;
    }
}
