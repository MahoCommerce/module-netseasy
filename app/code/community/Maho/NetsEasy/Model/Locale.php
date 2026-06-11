<?php

/**
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package Maho_NetsEasy
 */

declare(strict_types=1);

/**
 * Locale, currency validation, and country code mapping for Nets Easy API
 */
class Maho_NetsEasy_Model_Locale
{
    /**
     * Currencies supported by the Nets Easy API
     */
    public const SUPPORTED_CURRENCIES = [
        'DKK', 'SEK', 'NOK', 'EUR', 'USD', 'GBP', 'CHF', 'PLN',
    ];

    /**
     * Map of Nets-supported country codes to checkout locale strings
     */
    private const array COUNTRY_LOCALE_MAP = [
        'SWE' => 'sv-SE',
        'NOR' => 'nb-NO',
        'DNK' => 'da-DK',
        'FIN' => 'fi-FI',
        'DEU' => 'de-DE',
        'AUT' => 'de-AT',
        'CHE' => 'de-CH',
        'GBR' => 'en-GB',
        'USA' => 'en-US',
        'NLD' => 'nl-NL',
        'FRA' => 'fr-FR',
        'ESP' => 'es-ES',
        'ITA' => 'it-IT',
        'POL' => 'pl-PL',
        'BEL' => 'fr-BE',
    ];

    /**
     * Full ISO-2 to ISO-3 country code mapping
     */
    private const array ISO2_TO_ISO3 = [
        'AF' => 'AFG', 'AL' => 'ALB', 'DZ' => 'DZA', 'AS' => 'ASM', 'AD' => 'AND',
        'AO' => 'AGO', 'AG' => 'ATG', 'AR' => 'ARG', 'AM' => 'ARM', 'AU' => 'AUS',
        'AT' => 'AUT', 'AZ' => 'AZE', 'BS' => 'BHS', 'BH' => 'BHR', 'BD' => 'BGD',
        'BB' => 'BRB', 'BY' => 'BLR', 'BE' => 'BEL', 'BZ' => 'BLZ', 'BJ' => 'BEN',
        'BT' => 'BTN', 'BO' => 'BOL', 'BA' => 'BIH', 'BW' => 'BWA', 'BR' => 'BRA',
        'BN' => 'BRN', 'BG' => 'BGR', 'BF' => 'BFA', 'BI' => 'BDI', 'CV' => 'CPV',
        'KH' => 'KHM', 'CM' => 'CMR', 'CA' => 'CAN', 'CF' => 'CAF', 'TD' => 'TCD',
        'CL' => 'CHL', 'CN' => 'CHN', 'CO' => 'COL', 'KM' => 'COM', 'CG' => 'COG',
        'CD' => 'COD', 'CR' => 'CRI', 'HR' => 'HRV', 'CU' => 'CUB', 'CY' => 'CYP',
        'CZ' => 'CZE', 'DK' => 'DNK', 'DJ' => 'DJI', 'DM' => 'DMA', 'DO' => 'DOM',
        'EC' => 'ECU', 'EG' => 'EGY', 'SV' => 'SLV', 'GQ' => 'GNQ', 'ER' => 'ERI',
        'EE' => 'EST', 'SZ' => 'SWZ', 'ET' => 'ETH', 'FJ' => 'FJI', 'FI' => 'FIN',
        'FR' => 'FRA', 'GA' => 'GAB', 'GM' => 'GMB', 'GE' => 'GEO', 'DE' => 'DEU',
        'GH' => 'GHA', 'GR' => 'GRC', 'GD' => 'GRD', 'GT' => 'GTM', 'GN' => 'GIN',
        'GW' => 'GNB', 'GY' => 'GUY', 'HT' => 'HTI', 'HN' => 'HND', 'HK' => 'HKG',
        'HU' => 'HUN', 'IS' => 'ISL', 'IN' => 'IND', 'ID' => 'IDN', 'IR' => 'IRN',
        'IQ' => 'IRQ', 'IE' => 'IRL', 'IL' => 'ISR', 'IT' => 'ITA', 'JM' => 'JAM',
        'JP' => 'JPN', 'JO' => 'JOR', 'KZ' => 'KAZ', 'KE' => 'KEN', 'KI' => 'KIR',
        'KP' => 'PRK', 'KR' => 'KOR', 'KW' => 'KWT', 'KG' => 'KGZ', 'LA' => 'LAO',
        'LV' => 'LVA', 'LB' => 'LBN', 'LS' => 'LSO', 'LR' => 'LBR', 'LY' => 'LBY',
        'LI' => 'LIE', 'LT' => 'LTU', 'LU' => 'LUX', 'MO' => 'MAC', 'MG' => 'MDG',
        'MW' => 'MWI', 'MY' => 'MYS', 'MV' => 'MDV', 'ML' => 'MLI', 'MT' => 'MLT',
        'MH' => 'MHL', 'MR' => 'MRT', 'MU' => 'MUS', 'MX' => 'MEX', 'FM' => 'FSM',
        'MD' => 'MDA', 'MC' => 'MCO', 'MN' => 'MNG', 'ME' => 'MNE', 'MA' => 'MAR',
        'MZ' => 'MOZ', 'MM' => 'MMR', 'NA' => 'NAM', 'NR' => 'NRU', 'NP' => 'NPL',
        'NL' => 'NLD', 'NZ' => 'NZL', 'NI' => 'NIC', 'NE' => 'NER', 'NG' => 'NGA',
        'MK' => 'MKD', 'NO' => 'NOR', 'OM' => 'OMN', 'PK' => 'PAK', 'PW' => 'PLW',
        'PA' => 'PAN', 'PG' => 'PNG', 'PY' => 'PRY', 'PE' => 'PER', 'PH' => 'PHL',
        'PL' => 'POL', 'PT' => 'PRT', 'QA' => 'QAT', 'RO' => 'ROU', 'RU' => 'RUS',
        'RW' => 'RWA', 'KN' => 'KNA', 'LC' => 'LCA', 'VC' => 'VCT', 'WS' => 'WSM',
        'SM' => 'SMR', 'ST' => 'STP', 'SA' => 'SAU', 'SN' => 'SEN', 'RS' => 'SRB',
        'SC' => 'SYC', 'SL' => 'SLE', 'SG' => 'SGP', 'SK' => 'SVK', 'SI' => 'SVN',
        'SB' => 'SLB', 'SO' => 'SOM', 'ZA' => 'ZAF', 'SS' => 'SSD', 'ES' => 'ESP',
        'LK' => 'LKA', 'SD' => 'SDN', 'SR' => 'SUR', 'SE' => 'SWE', 'CH' => 'CHE',
        'SY' => 'SYR', 'TW' => 'TWN', 'TJ' => 'TJK', 'TZ' => 'TZA', 'TH' => 'THA',
        'TL' => 'TLS', 'TG' => 'TGO', 'TO' => 'TON', 'TT' => 'TTO', 'TN' => 'TUN',
        'TR' => 'TUR', 'TM' => 'TKM', 'TV' => 'TUV', 'UG' => 'UGA', 'UA' => 'UKR',
        'AE' => 'ARE', 'GB' => 'GBR', 'US' => 'USA', 'UY' => 'URY', 'UZ' => 'UZB',
        'VU' => 'VUT', 'VE' => 'VEN', 'VN' => 'VNM', 'YE' => 'YEM', 'ZM' => 'ZMB',
        'ZW' => 'ZWE',
    ];

    /**
     * Check if a currency is supported by Nets Easy
     */
    public function isCurrencySupported(string $currencyCode): bool
    {
        return in_array(strtoupper($currencyCode), self::SUPPORTED_CURRENCIES, true);
    }

    /**
     * Get the locale string to pass to the Nets Easy checkout widget
     * based on the current store locale
     */
    public function getCheckoutLocale(?int $storeId = null): string
    {
        $mahoLocale = Mage::getStoreConfig('general/locale/code', $storeId) ?: 'en_US';

        // Convert Maho locale (en_US) to hyphenated format (en-US)
        $locale = str_replace('_', '-', $mahoLocale);

        // Try exact match first (e.g., de-AT → de-AT)
        if (in_array($locale, self::COUNTRY_LOCALE_MAP, true)) {
            return $locale;
        }

        // Fall back to language-only match (e.g., de → de-DE)
        $language = explode('-', $locale)[0] ?? 'en';
        foreach (self::COUNTRY_LOCALE_MAP as $localeValue) {
            if (str_starts_with($localeValue, $language . '-')) {
                return $localeValue;
            }
        }

        return 'en-GB';
    }

    /**
     * Convert ISO-2 country code to ISO-3 (required by Nets API for addresses)
     */
    public function countryIso2ToIso3(string $iso2): string
    {
        $iso2 = strtoupper($iso2);
        return self::ISO2_TO_ISO3[$iso2] ?? $iso2;
    }

    /**
     * Get the locale string for a specific ISO-3 country code
     */
    public function getLocaleByCountryIso3(string $iso3): string
    {
        return self::COUNTRY_LOCALE_MAP[strtoupper($iso3)] ?? 'en-GB';
    }
}
