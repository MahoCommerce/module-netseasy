<?php

declare(strict_types=1);

/**
 * Maho
 *
 * @package    Maho_NetsEasy
 * @copyright  Copyright (c) 2026 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Maho_NetsEasy_Model_Api_Dto_RefundResponse
{
    public function __construct(
        private readonly array $data,
    ) {}

    public function getRefundId(): string
    {
        return (string) ($this->data['refundId'] ?? '');
    }

    public function getRawData(): array
    {
        return $this->data;
    }
}
