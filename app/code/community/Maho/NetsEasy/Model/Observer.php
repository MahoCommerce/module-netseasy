<?php

declare(strict_types=1);

/**
 * Maho
 *
 * @package    Maho_NetsEasy
 * @copyright  Copyright (c) 2026 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Maho_NetsEasy_Model_Observer
{
    /**
     * Observer for sales_order_payment_capture event.
     * Ensures the Nets payment ID is available on the payment object before capture.
     */
    public function capturePayment(\Maho\Event\Observer $observer): void
    {
        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $observer->getEvent()->getPayment();

        if ($payment->getMethodInstance()->getCode() !== 'netseasy') {
            return;
        }

        $paymentId = $payment->getAdditionalInformation('netseasy_payment_id');

        if (!$paymentId) {
            Mage::throwException(Mage::helper('netseasy')->__('Nets Easy payment ID is missing. Cannot capture payment.'));
        }
    }

    /**
     * Observer for sales_order_place_after event.
     * Updates the Nets Easy payment reference with the actual order increment ID
     * and links the order in the netseasy_payment tracking table.
     */
    public function updatePaymentReference(\Maho\Event\Observer $observer): void
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getEvent()->getOrder();
        $payment = $order->getPayment();

        if (!$payment || $payment->getMethod() !== 'netseasy') {
            return;
        }

        $paymentId = $payment->getAdditionalInformation('netseasy_payment_id');
        if (!$paymentId) {
            return;
        }

        // Update the payment reference in Nets Easy API with the order increment ID
        try {
            /** @var Maho_NetsEasy_Model_Api_Payment $apiPayment */
            $apiPayment = Mage::getModel('netseasy/api_payment');
            $apiPayment->updateReference(
                $paymentId,
                $order->getIncrementId(),
                (int) $order->getStoreId(),
            );
        } catch (\Exception $e) {
            // Log but don't block order placement
            Mage::log(
                sprintf('NetsEasy: Failed to update payment reference for %s: %s', $paymentId, $e->getMessage()),
                Mage::LOG_WARNING,
                'netseasy.log',
            );
        }

        // Link order_id in the netseasy_payment tracking table
        try {
            $resource = Mage::getSingleton('core/resource');
            /** @var Maho\Db\Adapter\AdapterInterface $connection */
            $connection = $resource->getConnection('core_write');
            $table = $resource->getTableName('netseasy/payment');

            $connection->update(
                $table,
                ['order_id' => (int) $order->getId()],
                ['payment_id = ?' => $paymentId],
            );
        } catch (\Exception $e) {
            Mage::log(
                sprintf('NetsEasy: Failed to link order to payment record for %s: %s', $paymentId, $e->getMessage()),
                Mage::LOG_WARNING,
                'netseasy.log',
            );
        }
    }
}
