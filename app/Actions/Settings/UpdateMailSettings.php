<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Actions\Audit\RecordSettingsChange;
use App\Models\User;
use App\Settings\MailSettings;

final class UpdateMailSettings
{
    public function __construct(public MailSettings $settings, public RecordSettingsChange $recordSettingsChange) {}

    /** @param array{mailer: string, smtp_host: ?string, smtp_port: ?int, smtp_scheme: ?string, smtp_username: ?string, smtp_password: ?string, from_address: string, from_name: string, show_logo: bool, footer_text: ?string} $data */
    public function handle(array $data, ?User $actor = null): void
    {
        $this->recordSettingsChange->handle($this->settings, $actor, function () use ($data): void {
            $this->settings->mailer = $data['mailer'];
            $this->settings->smtp_host = $data['smtp_host'] ?? '';
            $this->settings->smtp_port = $data['smtp_port'] ?? 587;
            $this->settings->smtp_scheme = $data['smtp_scheme'] ?? 'smtp';
            $this->settings->smtp_username = $data['smtp_username'] ?? '';

            if (filled($data['smtp_password'])) {
                $this->settings->smtp_password = $data['smtp_password'];
            }

            $this->settings->from_address = $data['from_address'];
            $this->settings->from_name = $data['from_name'];
            $this->settings->show_logo = $data['show_logo'];
            $this->settings->footer_text = $data['footer_text'] ?? '';
            $this->settings->save();
        });
    }
}
