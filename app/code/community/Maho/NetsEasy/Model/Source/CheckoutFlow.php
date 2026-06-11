<?php

/**
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package Maho_NetsEasy
 */

declare(strict_types=1);

class Maho_NetsEasy_Model_Source_CheckoutFlow
{
    public const EMBEDDED = 'embedded';
    public const HOSTED = 'hosted';

    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::EMBEDDED,
                'label' => Mage::helper('netseasy')->__('Embedded Checkout'),
            ],
            [
                'value' => self::HOSTED,
                'label' => Mage::helper('netseasy')->__('Hosted Payment Page'),
            ],
        ];
    }
}
