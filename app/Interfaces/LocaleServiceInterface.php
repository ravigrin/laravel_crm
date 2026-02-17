<?php

namespace App\Interfaces;

interface LocaleServiceInterface
{
    /**
     * Get email template by code and locale
     *
     * @param string $templateCode
     * @param string $locale
     * @return \App\Models\Email|null
     */
    public function getEmailTemplate(string $templateCode, string $locale = 'RU'): ?\App\Models\Email;

    /**
     * Get translation by key
     *
     * @param string $key
     * @param array $replace
     * @param string $locale
     * @return string
     */
    public function translate(string $key, array $replace = [], string $locale = 'RU'): string;

    /**
     * Get current locale
     *
     * @return string
     */
    public function getCurrentLocale(): string;

    /**
     * Set current locale
     *
     * @param string $locale
     * @return void
     */
    public function setCurrentLocale(string $locale): void;
}
