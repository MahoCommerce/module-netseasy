<?php

/**
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package Maho_NetsEasy
 */

declare(strict_types=1);

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Maho_NetsEasy_Model_Api_Client
{
    private const int TIMEOUT = 30;

    private ?HttpClientInterface $httpClient = null;

    private function getHelper(): Maho_NetsEasy_Helper_Data
    {
        return Mage::helper('netseasy');
    }

    public function get(string $endpoint, ?int $storeId = null): array
    {
        return $this->request('GET', $endpoint, [], $storeId);
    }

    public function post(string $endpoint, array $body = [], ?int $storeId = null): array
    {
        return $this->request('POST', $endpoint, $body, $storeId);
    }

    public function put(string $endpoint, array $body = [], ?int $storeId = null): array
    {
        return $this->request('PUT', $endpoint, $body, $storeId);
    }

    private function request(string $method, string $endpoint, array $body, ?int $storeId): array
    {
        $helper = $this->getHelper();
        $url = $helper->getApiBaseUrl($storeId) . $endpoint;
        $secretKey = $helper->getApiSecretKey($storeId);

        if ($secretKey === '') {
            $environment = $helper->isTestMode($storeId) ? 'test' : 'live';
            Mage::log(
                sprintf('NetsEasy API: missing %s secret key for store %s', $environment, $storeId ?? 'default'),
                Mage::LOG_ERROR,
                'netseasy.log',
            );
            Mage::throwException(Mage::helper('netseasy')->__(
                'Nets Easy secret key is not configured for this store (%s environment). Please set it in the payment method configuration.',
                $environment,
            ));
        }

        $options = [
            'headers' => [
                'Authorization' => $secretKey,
                'Content-Type' => 'application/json',
            ],
        ];

        if (!empty($body) && in_array($method, ['POST', 'PUT'], true)) {
            $options['body'] = Mage::helper('core')->jsonEncode($body);
        }

        if ($helper->isDebugEnabled($storeId)) {
            $sanitizedBody = $this->redactSensitiveFields($body);
            Mage::log(
                sprintf(
                    'NetsEasy API %s %s (store %s): %s',
                    $method,
                    $url,
                    $storeId ?? 'default',
                    Mage::helper('core')->jsonEncode($sanitizedBody),
                ),
                Mage::LOG_DEBUG,
                'netseasy.log',
            );
        }

        try {
            $response = $this->getHttpClient()->request($method, $url, $options);
            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);

            if ($helper->isDebugEnabled($storeId)) {
                Mage::log(
                    sprintf('NetsEasy API Response [%d]: %s', $statusCode, $content),
                    Mage::LOG_DEBUG,
                    'netseasy.log',
                );
            }

            if ($statusCode >= 400) {
                $errorData = [];
                if (!empty($content)) {
                    try {
                        $errorData = Mage::helper('core')->jsonDecode($content);
                    } catch (\JsonException) {
                        // Response is not JSON
                    }
                }

                $errorMessage = $this->extractErrorMessage($errorData, $statusCode);
                Mage::log(
                    sprintf('NetsEasy API Error [%d] %s %s: %s', $statusCode, $method, $endpoint, $content),
                    Mage::LOG_ERROR,
                    'netseasy.log',
                );
                Mage::throwException(Mage::helper('netseasy')->__('Nets Easy API error: %s', $errorMessage));
            }

            if (empty($content)) {
                return [];
            }

            return Mage::helper('core')->jsonDecode($content);
        } catch (Mage_Core_Exception $e) {
            throw $e;
        } catch (\Throwable $e) {
            Mage::log(
                sprintf('NetsEasy API Exception %s %s: %s', $method, $endpoint, $e->getMessage()),
                Mage::LOG_ERROR,
                'netseasy.log',
            );
            Mage::throwException(Mage::helper('netseasy')->__('Nets Easy API communication error. Please try again later.'));
        }
    }

    /**
     * Turn an error payload into something a human can act on.
     *
     * Nets reports validation failures as {"errors": {"<field>": ["<message>", ...]}},
     * where <field> is the offending property path and therefore differs per error.
     * It cannot be read through a fixed key, so flatten whatever is there instead —
     * otherwise every rejected request degrades to a bare "HTTP 400".
     */
    private function extractErrorMessage(array $errorData, int $statusCode): string
    {
        if (isset($errorData['errors']) && is_array($errorData['errors'])) {
            $messages = [];
            foreach ($errorData['errors'] as $property => $propertyErrors) {
                foreach ((array) $propertyErrors as $message) {
                    if (!is_string($message)) {
                        continue;
                    }
                    $messages[] = is_string($property)
                        ? sprintf('%s: %s', $property, $message)
                        : $message;
                }
            }
            if ($messages !== []) {
                return implode('; ', $messages);
            }
        }

        foreach (['message', 'detail', 'title', 'error'] as $key) {
            if (!empty($errorData[$key]) && is_string($errorData[$key])) {
                return $errorData[$key];
            }
        }

        return "HTTP {$statusCode}";
    }

    private function redactSensitiveFields(array $data): array
    {
        if (isset($data['notifications']['webhooks'])) {
            foreach ($data['notifications']['webhooks'] as &$webhook) {
                if (isset($webhook['authorization'])) {
                    $webhook['authorization'] = '***REDACTED***';
                }
            }
        }
        return $data;
    }

    private function getHttpClient(): HttpClientInterface
    {
        $this->httpClient ??= HttpClient::create([
            'timeout' => self::TIMEOUT,
        ]);
        return $this->httpClient;
    }
}
