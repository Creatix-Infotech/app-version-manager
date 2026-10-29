<?php

namespace Creatix\AppVersionManager\Services\Store;

use Creatix\AppVersionManager\Contracts\StoreVersionProvider;
use Creatix\AppVersionManager\Enums\Platform;
use Creatix\AppVersionManager\Support\StoreVersion;
use Firebase\JWT\JWT;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Reads the currently-live iOS version from the App Store Connect API.
 */
class AppStoreConnectClient implements StoreVersionProvider
{
    private const BASE = 'https://api.appstoreconnect.apple.com/v1';

    public function platform(): Platform
    {
        return Platform::IOS;
    }

    public function isConfigured(): bool
    {
        return filled($this->issuerId())
            && filled($this->keyId())
            && filled($this->privateKeyConfig())
            && filled($this->appId());
    }

    public function latestLiveVersion(): ?StoreVersion
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $privateKey = $this->privateKey();
        if (! str_contains($privateKey, 'BEGIN')) {
            Log::error('App Store Connect: private key did not resolve to a valid PEM key.');

            return null;
        }

        try {
            $response = Http::withToken($this->token($privateKey))
                ->acceptJson()
                ->retry(3, 1000, throw: false)
                ->get(self::BASE.'/apps/'.$this->appId().'/appStoreVersions', [
                    'filter[platform]' => 'IOS',
                    'filter[appStoreState]' => 'READY_FOR_SALE',
                    'include' => 'build',
                    'fields[appStoreVersions]' => 'versionString,appStoreState,build,createdDate',
                    'fields[builds]' => 'version',
                    'limit' => 20,
                ]);

            if ($response->failed()) {
                Log::error('App Store Connect version lookup failed', [
                    'status' => $response->status(),
                    'body' => $response->json() ?: $response->body(),
                ]);

                return null;
            }

            $data = $response->json('data', []);
            $included = collect($response->json('included', []));

            if (empty($data)) {
                return null;
            }

            $versions = collect($data)->map(function ($item) use ($included) {
                $versionString = $item['attributes']['versionString'] ?? null;
                $buildId = $item['relationships']['build']['data']['id'] ?? null;

                $buildResource = $included->first(function ($inc) use ($buildId) {
                    return ($inc['type'] ?? '') === 'builds' && ($inc['id'] ?? '') === $buildId;
                });

                $buildVersion = $buildResource['attributes']['version'] ?? null;

                if (blank($versionString) || blank($buildVersion)) {
                    return null;
                }

                return new StoreVersion((string) $versionString, (int) $buildVersion);
            })->filter()->values();

            if ($versions->isEmpty()) {
                return null;
            }

            return $versions->sortByDesc(fn (StoreVersion $v) => $v->build)->first();
        } catch (\Throwable $e) {
            Log::error('App Store Connect version lookup errored', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function token(string $privateKey): string
    {
        $now = Carbon::now()->getTimestamp();

        return JWT::encode(
            [
                'iss' => $this->issuerId(),
                'iat' => $now,
                'exp' => $now + 1200,
                'aud' => 'appstoreconnect-v1',
            ],
            $privateKey,
            'ES256',
            $this->keyId(),
        );
    }

    private function privateKey(): string
    {
        $key = (string) $this->privateKeyConfig();

        if (str_contains($key, 'BEGIN')) {
            return str_contains($key, '\n') ? str_replace('\n', "\n", $key) : $key;
        }

        if (Storage::disk('local')->exists($key)) {
            return (string) Storage::disk('local')->get($key);
        }

        if (is_file($key)) {
            return (string) file_get_contents($key);
        }

        return $key;
    }

    private function issuerId(): ?string
    {
        return config('app-version-manager.stores.appstore.issuer_id')
            ?: config('services.appstore.issuer_id');
    }

    private function keyId(): ?string
    {
        return config('app-version-manager.stores.appstore.key_id')
            ?: config('services.appstore.key_id');
    }

    private function privateKeyConfig(): ?string
    {
        return config('app-version-manager.stores.appstore.private_key')
            ?: config('services.appstore.private_key');
    }

    private function appId(): ?string
    {
        return config('app-version-manager.stores.appstore.app_id')
            ?: config('services.appstore.app_id');
    }
}

