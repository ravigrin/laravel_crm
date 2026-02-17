<?php

namespace App\Services;

use App\Interfaces\LocaleServiceInterface;
use App\Models\Email;
use Illuminate\Support\Facades\Cache;

class LocaleService implements LocaleServiceInterface
{
    protected string $currentLocale = 'RU';
    protected int $cacheTtl = 3600; // 1 hour

    /**
     * Get email template by code and locale
     *
     * @param string $templateCode
     * @param string $locale
     * @return \App\Models\Email|null
     */
    public function getEmailTemplate(string $templateCode, string $locale = 'RU'): ?Email
    {
        $cacheKey = "email_template_{$templateCode}_{$locale}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($templateCode, $locale) {
            return Email::where('locale_code', $locale)
                ->where('template_code', $templateCode)
                ->first();
        });
    }

    /**
     * Get translation by key
     *
     * @param string $key
     * @param array $replace
     * @param string $locale
     * @return string
     */
    public function translate(string $key, array $replace = [], string $locale = 'RU'): string
    {
        return trans($key, $replace, $locale);
    }

    /**
     * Get current locale
     *
     * @return string
     */
    public function getCurrentLocale(): string
    {
        return $this->currentLocale;
    }

    /**
     * Set current locale
     *
     * @param string $locale
     * @return void
     */
    public function setCurrentLocale(string $locale): void
    {
        $this->currentLocale = $locale;
    }

    /**
     * Get available locales
     *
     * @return array
     */
    public function getAvailableLocales(): array
    {
        return ['RU', 'EN', 'DE', 'FR']; // Add more as needed
    }

    /**
     * Check if locale is supported
     *
     * @param string $locale
     * @return bool
     */
    public function isLocaleSupported(string $locale): bool
    {
        return in_array(strtoupper($locale), $this->getAvailableLocales());
    }

    /**
     * Clear template cache
     *
     * @param string|null $templateCode
     * @param string|null $locale
     * @return void
     */
    public function clearTemplateCache(?string $templateCode = null, ?string $locale = null): void
    {
        if ($templateCode && $locale) {
            Cache::forget("email_template_{$templateCode}_{$locale}");
        } elseif ($templateCode) {
            foreach ($this->getAvailableLocales() as $loc) {
                Cache::forget("email_template_{$templateCode}_{$loc}");
            }
        } else {
            // Clear all template cache
            foreach ($this->getAvailableLocales() as $loc) {
                Cache::forget("email_template_*_{$loc}");
            }
        }
    }
}
