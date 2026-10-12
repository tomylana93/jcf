<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Actions\Audit\RecordSettingsChange;
use App\Enums\BrandColor;
use App\Enums\LogoType;
use App\Models\User;
use App\Settings\BrandSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

final class UpdateBrandSettings
{
    public function __construct(public BrandSettings $settings, public RecordSettingsChange $recordSettingsChange) {}

    /** @param array{color: string, logo_type?: string, logo_text?: UploadedFile|null, remove_logo_text?: bool, logo?: UploadedFile|null, favicon?: UploadedFile|null, auth_background?: UploadedFile|null, remove_logo?: bool, remove_favicon?: bool, remove_auth_background?: bool} $data */
    public function handle(array $data, ?User $actor = null): void
    {
        $oldPaths = [];
        $newPaths = [];

        try {
            $this->recordSettingsChange->handle($this->settings, $actor, function () use ($data, &$oldPaths, &$newPaths): void {

                foreach (['logo', 'logo_text', 'favicon', 'auth_background'] as $field) {
                    $property = $field.'_path';
                    $oldPath = $this->settings->{$property};

                    if (($data[$field] ?? null) instanceof UploadedFile) {
                        $path = $data[$field]->store('brand', 'public');

                        if ($path === false) {
                            throw ValidationException::withMessages([$field => __('settings.brand.error.store_failed')]);
                        }

                        $this->settings->{$property} = $path;
                        $newPaths[] = $path;
                    } elseif ($data['remove_'.$field] ?? false) {
                        $this->settings->{$property} = null;
                    }

                    if ($oldPath && $oldPath !== $this->settings->{$property}) {
                        $oldPaths[] = $oldPath;
                    }
                }

                if (isset($data['logo_type'])) {
                    $this->settings->logo_type = LogoType::from($data['logo_type']);
                }

                $this->settings->color = BrandColor::from($data['color']);
                $this->settings->save();
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($newPaths);

            throw $exception;
        }

        if ($oldPaths !== []) {
            Storage::disk('public')->delete($oldPaths);
        }
    }
}
