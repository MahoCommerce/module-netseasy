<?php

/**
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package Maho_NetsEasy
 */

declare(strict_types=1);

class Maho_NetsEasy_Model_Api_Dto_ChargeRequest extends Maho_NetsEasy_Model_Api_Dto_AbstractRequest
{
    /** @var Maho_NetsEasy_Model_Api_Dto_OrderItem[] */
    private array $orderItems = [];

    public function __construct(
        private readonly int $amount,
    ) {}

    /**
     * @param Maho_NetsEasy_Model_Api_Dto_OrderItem[] $items
     */
    public function setOrderItems(array $items): self
    {
        $this->orderItems = $items;
        return $this;
    }

    #[\Override]
    public function toArray(): array
    {
        $items = array_map(
            static fn(Maho_NetsEasy_Model_Api_Dto_OrderItem $item) => $item->toArray(),
            $this->orderItems,
        );

        return [
            'amount' => $this->amount,
            'orderItems' => $items,
        ];
    }
}
