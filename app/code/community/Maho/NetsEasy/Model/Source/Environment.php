<?php

/**
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package Maho_NetsEasy
 */

declare(strict_types=1);

class Maho_NetsEasy_Model_Source_Environment
{
    public const TEST = 'test';
    public const LIVE = 'live';

    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::TEST,
                'label' => Mage::helper('netseasy')->__('Test'),
            ],
            [
                'value' => self::LIVE,
                'label' => Mage::helper('netseasy')->__('Live'),
            ],
        ];
    }
}
