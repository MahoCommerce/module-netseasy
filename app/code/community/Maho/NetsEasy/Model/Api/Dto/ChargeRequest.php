<?php

declare(strict_types=1);

/**
 * Maho
 *
 * @package    Maho_NetsEasy
 * @copyright  Copyright (c) 2026 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

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
