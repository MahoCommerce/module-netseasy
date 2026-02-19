<?php

declare(strict_types=1);

/**
 * Maho
 *
 * @package    Maho_NetsEasy
 * @copyright  Copyright (c) 2026 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

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
