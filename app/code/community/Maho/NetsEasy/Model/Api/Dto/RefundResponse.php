<?php

/**
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package Maho_NetsEasy
 */

declare(strict_types=1);

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
