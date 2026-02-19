<?php

declare(strict_types=1);

/**
 * Maho
 *
 * @package    Maho_NetsEasy
 * @copyright  Copyright (c) 2026 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

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
