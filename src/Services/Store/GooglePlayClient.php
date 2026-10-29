<?php

namespace Creatix\AppVersionManager\Services\Store;

use Creatix\AppVersionManager\Contracts\StoreVersionProvider;
use Creatix\AppVersionManager\Enums\Platform;
use Creatix\AppVersionManager\Support\StoreVersion;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Reads the currently-live Android version from the Google Play Developer API.
 */
class GooglePlayClient implements StoreVersionProvider
{
    private const BASE = 'https://androidpublisher.googleapis.com/androidpublisher/v3';

    private const SCOPE = 'https://www.googleapis.com/auth/androidpublisher';

    public function platform(): Platform
    {
        return Platform::ANDROID;
    }

    public function isConfigured(): bool
    {
        return filled($this->packageName())
            && $this->serviceAccountPath() !== null;
    }

    public function latestLiveVersion(): ?StoreVersion
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $package = $this->packageName();

        try {
            $http = Http::withToken($this->token())
                ->acceptJson()
                ->retry(3, 1000, throw: false);

            $track = null;
            for ($attempt = 1; $attempt <= 2; $attempt++) {
                $editId = $this->openEdit($http, $package);
                if ($editId === null) {
                    return null;
                }

                $track = $http->get(self::BASE."/applications/{$package}/edits/{$editId}/tracks/production");

                $http->delete(self::BASE."/applications/{$package}/edits/{$editId}");

                if ($track->successful() || ! $this->isStaleEditError($track)) {
                    break;
                }
            }

            if ($track === null || $track->failed()) {
                Log::error('Google Play track fetch failed', [
                    'status' => $track?->status(),
                    'body' => $track?->json() ?: $track?->body(),
                ]);

                return null;
            }

            $release = collect($track->json('releases', []))
                ->firstWhere('status', 'completed');

            if (! $release) {
                return null;
            }

            $build = collect($release['versionCodes'] ?? [])->map(fn ($c) => (int) $c)->max();
            $version = $release['name'] ?? null;

            if (blank($build) || blank($version)) {
                return null;
            }

            return new StoreVersion((string) $version, (int) $build);
        } catch (\Throwable $e) {
            Log::error('Google Play version lookup errored', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function openEdit(PendingRequest $http, string $package): ?string
    {
        $edit = $http->post(self::BASE."/applications/{$package}/edits");

        if ($edit->failed()) {
            Log::error('Google Play edit open failed', [
                'status' => $edit->status(),
                'body' => $edit->json() ?: $edit->body(),
            ]);

            return null;
        }

        $id = $edit->json('id');

        return filled($id) ? (string) $id : null;
    }

    private function isStaleEditError(Response $response): bool
    {
        if ($response->status() !== 400) {
            return false;
        }

        $body = $response->json();

        return data_get($body, 'error.status') === 'FAILED_PRECONDITION'
            || str_contains((string) data_get($body, 'error.message'), 'Edit has been deleted');
    }

    private function token(): string
    {
        $creds = new ServiceAccountCredentials(self::SCOPE, $this->serviceAccountPath());

        return $creds->fetchAuthToken()['access_token'];
    }

    private function serviceAccountPath(): ?string
    {
        $value = (string) (config('app-version-manager.stores.googleplay.service_account')
            ?: config('services.googleplay.service_account'));

        if ($value !== '' && Storage::disk('local')->exists($value)) {
            return Storage::disk('local')->path($value);
        }

        return is_file($value) ? $value : null;
    }

    private function packageName(): ?string
    {
        return config('app-version-manager.stores.googleplay.package_name')
            ?: config('services.googleplay.package_name');
    }
}

