<?php
namespace App\Jobs;

use App\Exceptions\ClientException;
use App\Helpers\Locale;
use App\Helpers\MailClient;

class NotifyByEmail extends Job
{
    protected $email;
    protected $body;
    protected $templateCode;

    public function __construct($email, $body, $templateCode)
    {
        $this->email = $email;
        $this->body = $body;
        $this->templateCode = $templateCode;
    }

    public function handle()
    {
        try {
            (new MailClient())->send(
                $this->email,
                Locale::getEmailTemplate($this->templateCode),
                $this->body
            );
        } catch (ClientException $exception) {
            \Log::critical($exception->getMessage(), $exception->getTrace());
        }
    }
}
