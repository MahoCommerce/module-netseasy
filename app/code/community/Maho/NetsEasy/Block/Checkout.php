<?php

/**
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package Maho_NetsEasy
 */

declare(strict_types=1);

class Maho_NetsEasy_Block_Checkout extends Mage_Core_Block_Template
{
    #[\Override]
    protected function _construct(): void
    {
        parent::_construct();
        $this->setTemplate('maho/netseasy/checkout.phtml');
    }

    public function getPaymentId(): string
    {
        return (string) $this->getData('payment_id');
    }

    public function getCheckoutKey(): string
    {
        return (string) $this->getData('checkout_key');
    }

    public function getReturnUrl(): string
    {
        return (string) $this->getData('return_url');
    }

    public function getLocale(): string
    {
        /** @var Maho_NetsEasy_Model_Locale $locale */
        $locale = Mage::getSingleton('netseasy/locale');
        return $locale->getCheckoutLocale();
    }

    public function getCheckoutScriptUrl(): string
    {
        /** @var Maho_NetsEasy_Helper_Data $helper */
        $helper = Mage::helper('netseasy');
        return $helper->getCheckoutScriptUrl();
    }

    public function getMsgProcessing(): string
    {
        return $this->__('Processing payment...');
    }

    public function getMsgMissingConfig(): string
    {
        return $this->__('Missing checkout configuration.');
    }

    public function getMsgLoadFailed(): string
    {
        return $this->__('Failed to load payment widget. Please refresh the page.');
    }
}
