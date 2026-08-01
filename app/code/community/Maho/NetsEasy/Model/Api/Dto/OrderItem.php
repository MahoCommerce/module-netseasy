<?php

/**
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package Maho_NetsEasy
 */

declare(strict_types=1);

class Maho_NetsEasy_Model_Api_Dto_OrderItem
{
    public function __construct(
        public readonly string $reference,
        public readonly string $name,
        public readonly int $quantity,
        public readonly string $unit,
        public readonly int $unitPrice,
        public readonly int $taxRate,
        public readonly int $taxAmount,
        public readonly int $grossTotalAmount,
        public readonly int $netTotalAmount,
    ) {}

    public static function fromOrderItem(Mage_Sales_Model_Order_Item $item): self
    {
        $qty = (int) $item->getQtyOrdered();
        $unitPrice = self::toMinorUnits((float) $item->getBasePrice());
        $taxPercent = (float) $item->getTaxPercent();
        $taxRate = (int) round($taxPercent * 100);
        $taxAmount = self::toMinorUnits((float) $item->getBaseTaxAmount());
        $netTotalAmount = $unitPrice * $qty;
        $grossTotalAmount = $netTotalAmount + $taxAmount;

        return new self(
            reference: (string) ($item->getSku() ?: $item->getProductId()),
            name: self::sanitizeName((string) $item->getName()),
            quantity: $qty,
            unit: 'pcs',
            unitPrice: $unitPrice,
            taxRate: $taxRate,
            taxAmount: $taxAmount,
            grossTotalAmount: $grossTotalAmount,
            netTotalAmount: $netTotalAmount,
        );
    }

    /**
     * Create an OrderItem for shipping line
     */
    public static function fromShipping(float $amount, float $taxAmount, float $taxPercent): self
    {
        $unitPrice = self::toMinorUnits($amount);
        $tax = self::toMinorUnits($taxAmount);

        return new self(
            reference: 'shipping',
            name: 'Shipping',
            quantity: 1,
            unit: 'pcs',
            unitPrice: $unitPrice,
            taxRate: (int) round($taxPercent * 100),
            taxAmount: $tax,
            grossTotalAmount: $unitPrice + $tax,
            netTotalAmount: $unitPrice,
        );
    }

    /**
     * Create the line that reconciles the item totals with the requested amount.
     *
     * $amountMinor is the signed difference in minor units: negative for a discount
     * (the usual case), positive for a surcharge or a rounding shortfall.
     */
    public static function fromAdjustment(int $amountMinor): self
    {
        return new self(
            reference: $amountMinor < 0 ? 'discount' : 'adjustment',
            name: $amountMinor < 0 ? 'Discount' : 'Adjustment',
            quantity: 1,
            unit: 'pcs',
            unitPrice: $amountMinor,
            taxRate: 0,
            taxAmount: 0,
            grossTotalAmount: $amountMinor,
            netTotalAmount: $amountMinor,
        );
    }

    public function toArray(): array
    {
        return [
            'reference' => $this->reference,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'unitPrice' => $this->unitPrice,
            'taxRate' => $this->taxRate,
            'taxAmount' => $this->taxAmount,
            'grossTotalAmount' => $this->grossTotalAmount,
            'netTotalAmount' => $this->netTotalAmount,
        ];
    }

    /**
     * Convert a float amount to minor units (cents)
     */
    public static function toMinorUnits(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /**
     * Sanitize item name for API (max 128 chars, no special chars that could break JSON)
     */
    private static function sanitizeName(string $name): string
    {
        return mb_substr(trim($name), 0, 128);
    }
}
