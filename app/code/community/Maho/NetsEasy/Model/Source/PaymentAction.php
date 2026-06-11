<?php

/**
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package Maho_NetsEasy
 */

declare(strict_types=1);

class Maho_NetsEasy_Model_Source_PaymentAction
{
    public function toOptionArray(): array
    {
        return [
            [
                'value' => Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE,
                'label' => Mage::helper('netseasy')->__('Authorize Only'),
            ],
            [
                'value' => Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE,
                'label' => Mage::helper('netseasy')->__('Authorize and Capture'),
            ],
        ];
    }
}
