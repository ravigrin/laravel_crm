<?php

namespace App\Services;

use App\Interfaces\LocaleServiceInterface;
use App\Models\Lead;
use Illuminate\Support\Arr;

class FieldMapperService
{
    protected LocaleServiceInterface $localeService;

    public function __construct(LocaleServiceInterface $localeService)
    {
        $this->localeService = $localeService;
    }

    /**
     * Build data from field mappings
     */
    public function buildFromMapping(
        Lead $lead,
        array $credentials,
        string $integration,
        string $section = 'fields'
    ): array {
        $mapping = config("integrations.{$integration}.{$section}", []);
        $result = [];

        foreach ($mapping as $key => $rule) {
            $value = $this->resolveFieldValue($lead, $credentials, $rule);
            
            if ($value !== null) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Resolve field value based on rule type
     */
    protected function resolveFieldValue(Lead $lead, array $credentials, array $rule)
    {
        switch ($rule['type']) {
            case 'attr':
                return data_get($lead, $rule['key']);
                
            case 'credentials':
                $value = data_get($credentials, $rule['key']);
                return $value ?? ($rule['fallback'] ?? null);
                
            case 'data':
                return data_get($lead->data, $rule['key']);
                
            case 'trans':
                $value = $this->localeService->translate($rule['key'], [], $rule['locale'] ?? 'RU');
                return $value ?? ($rule['fallback'] ?? null);
                
            case 'const':
                return $rule['value'];
                
            case 'date':
                $date = data_get($lead, $rule['key']);
                return $date ? $date->format('Y-m-d H:i:s') : null;
                
            case 'answers_text':
                return $this->convertAnswers($lead);
                
            case 'answers_html':
                return $this->convertAnswers($lead, true);
                
            case 'complex':
                return $this->buildComplexField($lead, $rule);
                
            case 'dynamic':
                return $this->buildDynamicField($lead, $rule);
                
            default:
                return null;
        }
    }

    /**
     * Build complex field structures
     */
    protected function buildComplexField(Lead $lead, array $rule)
    {
        switch ($rule['key']) {
            case 'phone':
                return [[
                    'VALUE' => $lead->phone,
                    'VALUE_TYPE' => 'MOBILE'
                ]];
                
            case 'email':
                return [[
                    'VALUE' => $lead->email,
                    'VALUE_TYPE' => 'HOME'
                ]];
                
            default:
                return null;
        }
    }

    /**
     * Build dynamic field with template
     */
    protected function buildDynamicField(Lead $lead, array $rule)
    {
        $value = data_get($lead, $rule['key']);
        
        if (isset($rule['template'])) {
            return str_replace('{value}', $value, $rule['template']);
        }
        
        return $value;
    }

    /**
     * Build nested structure from mapping
     */
    public function buildNestedFromMapping(
        Lead $lead,
        array $credentials,
        string $integration,
        string $section
    ): array {
        $mapping = config("integrations.{$integration}.{$section}", []);
        $result = [];

        foreach ($mapping as $key => $rule) {
            if (is_array($rule) && isset($rule['type'])) {
                // Single field
                $value = $this->resolveFieldValue($lead, $credentials, $rule);
                if ($value !== null) {
                    $result[$key] = $value;
                }
            } elseif (is_array($rule) && is_numeric($key)) {
                // Array item (like contacts array)
                $item = [];
                foreach ($rule as $subKey => $subRule) {
                    if (is_array($subRule) && isset($subRule['type'])) {
                        $value = $this->resolveFieldValue($lead, $credentials, $subRule);
                        if ($value !== null) {
                            $item[$subKey] = $value;
                        }
                    }
                }
                if (!empty($item)) {
                    $result[] = $item;
                }
            } elseif (is_array($rule)) {
                // Nested structure
                $result[$key] = $this->buildNestedFromMapping($lead, $credentials, $integration, $section . '.' . $key);
            }
        }

        return $result;
    }

    /**
     * Convert answers to text format
     */
    public function convertAnswers(
        Lead $lead,
        bool $html = false,
        bool $markdown = false,
        bool $showUtm = true,
        string $lineBreak = '',
        string $format = ''
    ): string {
        $answers = array_key_exists('answers2', $lead->data) ? $lead->data['answers2'] : [];
        $extra = array_key_exists('extra', $lead->data) ? $lead->data['extra'] : [];
        $lb = $html ? '<br>' : '\n';

        if (!empty($lineBreak)) {
            $lb = $lineBreak;
        }

        $applyFormat = function ($str, $link = '', $format = 'bold', $html = false, $markdown = false) {
            if ($format || $html || $markdown) {
                switch ($format) {
                    case 'italic':
                        return $markdown ? "<i>{$str}</i>" : "__{$str}__";
                    case 'bold':
                        return $markdown ? "<b>{$str}</b>" : "**{$str}**";
                    case 'link':
                        return $markdown ? "<a href=\"{$link}\" target=\"_blank\">{$str}</a>" : "[{$link}]($str)";
                }
            } elseif ($format === 'link') {
                return $link;
            } else {
                return $str;
            }
        };

        $text = '';

        // Add messengers
        foreach (\App\Enums\Messengers::getValues() as $messengerCode) {
            if (!array_key_exists($messengerCode, $lead->messengers)) {
                continue;
            }
            $formated = $applyFormat($messengerCode);
            $contact = $lead->messengers[$messengerCode];
            $text .= "{$formated}:{$contact}{$lb}";
        }

        // Add answers
        foreach ($answers as $answer) {
            if (is_array($answer['a'])) {
                if ($answers['t'] === 'file') {
                    foreach ($answer as $key => $value) {
                        $fileLocalized = $this->localeService->translate('lead.file', [], 'RU');
                        $answer[$key] = $applyFormat($fileLocalized, $value, 'link');
                    }
                    $answer = implode(', ', $answer);
                }
            }
            $formatedQuestion = $applyFormat($answer['q']);
            $text .= "{$formatedQuestion} {$lb} {$answer} {$lb} {$lb}";
        }

        // Add result
        if (array_key_exists('result', $lead->data)) {
            $formatedResult = $applyFormat($this->localeService->translate('lead.result', [], 'RU'));
            if (array_key_exists('title', $lead->data['result'])) {
                $text .= "{$formatedResult}: {$lead->data['result']['title']}{$lb}";
            }
            if (array_key_exists('link', $lead->data['result'])) {
                $text .= "{$formatedResult}: {$lead->data['result']['link']}{$lb}";
            }
        }

        // Add UTM
        if ($showUtm) {
            $utm = $applyFormat($this->localeService->translate('lead.utm', [], 'RU'));
            $text .= "{$utm}{$lb}";

            foreach ($lead->getUtmTags() as $tag => $value) {
                $value = $applyFormat($value, '', 'italic');
                $text .= "{$tag}: {$value} {$lb}";
            }
        }

        // Add extra data
        if (!empty($extra)) {
            if (array_key_exists('discount', $extra)) {
                $discount = $applyFormat($this->localeService->translate('lead.discount', [], 'RU'));
                $text .= "{$lb}{$discount}: {$extra['discount']} {$lb}";
            }

            if (array_key_exists('href', $extra)) {
                $page = $applyFormat($this->localeService->translate('lead.page', [], 'RU'));
                $text .= "{$lb}{$page}: {$extra['href']} {$lb}";
            }
        }

        return $text;
    }

    /**
     * Convert contacts to text
     */
    public function contactsToText(array $contacts = []): string
    {
        if (empty($contacts)) {
            return '';
        }

        $result = '';

        foreach ($contacts as $code => $value) {
            $label = $this->localeService->translate("lead.{$code}", [], 'ru');
            $result .= "<b>{$label}: </b>{$value}";
            $result .= "\n";
        }

        return $result;
    }
}
