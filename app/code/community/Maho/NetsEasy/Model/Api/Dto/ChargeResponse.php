<?php

/**
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package Maho_NetsEasy
 */

declare(strict_types=1);

class Maho_NetsEasy_Model_Api_Dto_ChargeResponse
{
    public function __construct(
        private readonly array $data,
    ) {}

    public function getChargeId(): string
    {
        return (string) ($this->data['chargeId'] ?? '');
    }

    public function getRawData(): array
    {
        return $this->data;
    }
}
