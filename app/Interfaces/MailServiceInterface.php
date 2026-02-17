<?php

namespace App\Interfaces;

interface MailServiceInterface
{
    /**
     * Send email via configured mail service
     *
     * @param string $address
     * @param string $template
     * @param array $data
     * @return bool
     */
    public function send(string $address, string $template, array $data = []): bool;

    /**
     * Send email with template
     *
     * @param string $address
     * @param string $templateId
     * @param array $templateData
     * @return bool
     */
    public function sendWithTemplate(string $address, string $templateId, array $templateData = []): bool;
}
