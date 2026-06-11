<?php

/**
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package Maho_NetsEasy
 */

declare(strict_types=1);

class Maho_NetsEasy_Block_Info extends Mage_Payment_Block_Info
{
    #[\Override]
    protected function _construct(): void
    {
        parent::_construct();
        $this->setTemplate('maho/netseasy/info.phtml');
    }

    #[\Override]
    protected function _prepareSpecificInformation($transport = null): \Maho\DataObject
    {
        if ($this->_paymentSpecificInformation !== null) {
            return $this->_paymentSpecificInformation;
        }

        $transport = parent::_prepareSpecificInformation($transport);
        $info = $this->getInfo();

        $paymentId = $info->getAdditionalInformation('netseasy_payment_id');
        if ($paymentId) {
            $transport->setData($this->__('Payment ID'), $paymentId);
        }

        $paymentType = $info->getAdditionalInformation('netseasy_payment_type');
        if ($paymentType) {
            $transport->setData($this->__('Payment Type'), $paymentType);
        }

        $paymentMethod = $info->getAdditionalInformation('netseasy_payment_method');
        if ($paymentMethod) {
            $transport->setData($this->__('Payment Method'), $paymentMethod);
        }

        $maskedPan = $info->getAdditionalInformation('netseasy_masked_pan');
        if ($maskedPan) {
            $transport->setData($this->__('Card Number'), $maskedPan);
        }

        $chargeId = $info->getAdditionalInformation('netseasy_last_charge_id');
        if ($chargeId) {
            $transport->setData($this->__('Charge ID'), $chargeId);
        }

        return $transport;
    }
}
