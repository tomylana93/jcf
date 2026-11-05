<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enums\Mailer;
use App\Settings\MailSettings;
use Illuminate\Mail\MailManager;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

final class SettingsMailTransport implements TransportInterface
{
    public function __construct(public MailManager $mailManager) {}

    public function __toString(): string
    {
        return 'settings-mail';
    }

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        $settings = resolve(MailSettings::class);
        $settings->refresh();

        if ($message instanceof Email) {
            $message->from(new Address($settings->from_address, $settings->from_name));
            $envelope = null;
        }

        $mailer = Mailer::tryFrom($settings->mailer);

        if ($mailer === Mailer::Environment) {
            return $this->mailManager->mailer(config('mail.environment_mailer'))->getSymfonyTransport()->send($message, $envelope);
        }

        if ($mailer === Mailer::Log) {
            return $this->mailManager->mailer('log')->getSymfonyTransport()->send($message, $envelope);
        }

        $transport = $this->mailManager->createSymfonyTransport([
            'transport' => 'smtp',
            'scheme' => $settings->smtp_scheme === 'none' ? 'smtp' : $settings->smtp_scheme,
            'auto_tls' => $settings->smtp_scheme !== 'none',
            'host' => $settings->smtp_host,
            'port' => $settings->smtp_port,
            'username' => $settings->smtp_username,
            'password' => $settings->smtp_password,
            'require_tls' => $settings->smtp_scheme === 'smtp',
            'timeout' => 10,
        ]);

        return $transport->send($message, $envelope);
    }
}
