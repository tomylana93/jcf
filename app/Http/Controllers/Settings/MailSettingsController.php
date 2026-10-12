<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\GetMailPreview;
use App\Actions\Settings\GetMailSettings;
use App\Actions\Settings\SendMailSettingsTest;
use App\Actions\Settings\UpdateMailSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\SendMailSettingsTestRequest;
use App\Http\Requests\Settings\UpdateMailSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Throwable;

final class MailSettingsController extends Controller
{
    public function edit(GetMailSettings $getSettings): Response
    {
        return Inertia::render('settings/Mail', [
            'settings' => $getSettings->handle(),
        ]);
    }

    public function update(UpdateMailSettingsRequest $request, UpdateMailSettings $updateSettings): RedirectResponse
    {
        $updateSettings->handle($request->validated(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('settings.mail.status.updated')]);

        return to_route('settings.mail.edit');
    }

    public function preview(GetMailPreview $preview): MailMessage
    {
        return $preview->handle();
    }

    public function test(SendMailSettingsTestRequest $request, SendMailSettingsTest $sendTest): RedirectResponse
    {
        try {
            $sendTest->handle($request->validated('test_email'));
        } catch (Throwable) {
            report(new RuntimeException('Mail settings test delivery failed.'));

            throw ValidationException::withMessages(['test_email' => __('settings.mail.status.test_failed')]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('settings.mail.status.test_sent')]);

        return to_route('settings.mail.edit');
    }
}
