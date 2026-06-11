<?php

/**
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package Maho_NetsEasy
 */

declare(strict_types=1);

class Maho_NetsEasy_Block_Redirect extends Mage_Core_Block_Template
{
    #[\Override]
    protected function _construct(): void
    {
        parent::_construct();
        $this->setTemplate('maho/netseasy/redirect.phtml');
    }

    public function getRedirectUrl(): string
    {
        return (string) $this->getData('redirect_url');
    }
}
