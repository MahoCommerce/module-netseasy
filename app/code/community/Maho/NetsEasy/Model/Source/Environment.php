<?php

declare(strict_types=1);

/**
 * Maho
 *
 * @package    Maho_NetsEasy
 * @copyright  Copyright (c) 2026 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

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
