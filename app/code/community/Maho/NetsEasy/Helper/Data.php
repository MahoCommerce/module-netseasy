<?php

declare(strict_types=1);

/**
 * Maho
 *
 * @package    Maho_NetsEasy
 * @copyright  Copyright (c) 2026 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Maho_NetsEasy_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_moduleName = 'Maho_NetsEasy';

    public const API_URL_TEST = 'https://test.api.dibspayment.eu';
    public const API_URL_LIVE = 'https://api.dibspayment.eu';

    public const CHECKOUT_JS_TEST = 'https://test.checkout.dibspayment.eu/v1/checkout.js';
    public const CHECKOUT_JS_LIVE = 'https://checkout.dibspayment.eu/v1/checkout.js';

    public const CONFIG_PATH_PREFIX = 'payment/netseasy/';

    public function getApiSecretKey(?int $storeId = null): string
    {
        $field = $this->isTestMode($storeId) ? 'test_secret_key' : 'live_secret_key';
        return $this->getDecryptedConfig($field, $storeId);
    }

    public function getCheckoutKey(?int $storeId = null): string
    {
        $field = $this->isTestMode($storeId) ? 'test_checkout_key' : 'live_checkout_key';
        return (string) Mage::getStoreConfig(self::CONFIG_PATH_PREFIX . $field, $storeId);
    }

    public function getApiBaseUrl(?int $storeId = null): string
    {
        return $this->isTestMode($storeId) ? self::API_URL_TEST : self::API_URL_LIVE;
    }

    /**
     * URL of the Nets embedded-checkout JS SDK. The test environment requires the
     * test SDK; loading the production SDK with a test session renders nothing.
     */
    public function getCheckoutScriptUrl(?int $storeId = null): string
    {
        return $this->isTestMode($storeId) ? self::CHECKOUT_JS_TEST : self::CHECKOUT_JS_LIVE;
    }

    public function getCheckoutFlow(?int $storeId = null): string
    {
        return (string) Mage::getStoreConfig(self::CONFIG_PATH_PREFIX . 'checkout_flow', $storeId);
    }

    public function isTestMode(?int $storeId = null): bool
    {
        return Mage::getStoreConfig(self::CONFIG_PATH_PREFIX . 'environment', $storeId) === 'test';
    }

    public function getWebhookSecret(?int $storeId = null): string
    {
        return $this->getDecryptedConfig('webhook_secret', $storeId);
    }

    /**
     * Reads an encrypted config field (backend_model adminhtml/system_config_backend_encrypted)
     * and returns the decrypted, trimmed plaintext. Mage::getStoreConfig() returns the stored
     * ciphertext, so it must be passed through the encryptor before use.
     */
    private function getDecryptedConfig(string $field, ?int $storeId): string
    {
        $value = (string) Mage::getStoreConfig(self::CONFIG_PATH_PREFIX . $field, $storeId);
        if ($value === '') {
            return '';
        }
        return trim(Mage::helper('core')->decrypt($value));
    }

    public function getTermsUrl(?int $storeId = null): string
    {
        return (string) Mage::getStoreConfig(self::CONFIG_PATH_PREFIX . 'terms_url', $storeId);
    }

    public function shouldSendOrderItems(?int $storeId = null): bool
    {
        return (bool) Mage::getStoreConfigFlag(self::CONFIG_PATH_PREFIX . 'send_order_items', $storeId);
    }

    public function getMerchantHandlesConsumerData(?int $storeId = null): bool
    {
        return (bool) Mage::getStoreConfigFlag(self::CONFIG_PATH_PREFIX . 'merchant_handles_consumer_data', $storeId);
    }

    public function getPaymentAction(?int $storeId = null): string
    {
        return (string) Mage::getStoreConfig(self::CONFIG_PATH_PREFIX . 'payment_action', $storeId);
    }

    public function isDebugEnabled(?int $storeId = null): bool
    {
        return (bool) Mage::getStoreConfigFlag(self::CONFIG_PATH_PREFIX . 'debug', $storeId);
    }
}
