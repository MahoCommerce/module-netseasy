<?php

/**
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package Maho_NetsEasy
 */

declare(strict_types=1);

class Maho_NetsEasy_Block_Form extends Mage_Payment_Block_Form
{
    #[\Override]
    protected function _construct(): void
    {
        parent::_construct();
        $this->setTemplate('maho/netseasy/form.phtml');
    }
}
