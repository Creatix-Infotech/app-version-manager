<?php

namespace Creatix\AppVersionManager\Services;

use Creatix\AppVersionManager\Contracts\StoreVersionProvider;
use Creatix\AppVersionManager\Enums\Platform;
use Creatix\AppVersionManager\Events\StoreVersionReleased;
use Creatix\AppVersionManager\Models\AppVersion;
use Creatix\AppVersionManager\Support\StoreVersion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Promotes a build to "latest" in the app_versions table when a store reports it live.
 */
class StoreVersionSyncer
{
    /**
     * @param  iterable<StoreVersionProvider>  $providers
     */
    public function __construct(private iterable $providers) {}

    /**
     * Run every configured provider.
     *
     * @return array<int, string>
     */
    public function sync(): array
    {
        $results = [];

        foreach ($this->providers as $provider) {
            $platform = $provider->platform()->value;

            if (! $provider->isConfigured()) {
                $results[] = "{$platform}: skipped (not configured)";

                continue;
            }

            $live = $provider->latestLiveVersion();

            if ($live === null) {
                $results[] = "{$platform}: no live version found";

                continue;
            }

            $results[] = $this->apply($platform, $live);
        }

        return $results;
    }

    private function apply(string $platform, StoreVersion $live): string
    {
        $modelClass = config('app-version-manager.model', AppVersion::class);

        $current = $modelClass::where('platform', $platform)
            ->orderByDesc('latest_build')
            ->orderByDesc('created_at')
            ->first();

        if ($current === null) {
            $created = $modelClass::create([
                'platform' => $platform,
                'latest_version' => $live->version,
                'latest_build' => $live->build,
                'minimum_version' => $live->version,
                'minimum_build' => $live->build,
                'store_url' => $this->defaultStoreUrl($platform),
                'update_message' => config('app-version-manager.defaults.update_message'),
                'is_active' => true,
            ]);

            event(new StoreVersionReleased($created, $live, 0, 'created'));
            $this->notify($platform, $live, 0);

            return "{$platform}: created initial version {$live->version} (build {$live->build})";
        }

        if ($live->build <= $current->latest_build) {
            if (! $current->is_active) {
                $modelClass::where('platform', $platform)->update(['is_active' => false]);
                $current->update(['is_active' => true]);
            }

            return "{$platform}: up to date (build {$current->latest_build})";
        }

        $previous = $current->latest_build;

        $modelClass::where('platform', $platform)->update(['is_active' => false]);

        $created = $modelClass::create([
            'platform' => $platform,
            'latest_version' => $live->version,
            'latest_build' => $live->build,
            'minimum_version' => $current->minimum_version,
            'minimum_build' => $current->minimum_build,
            'store_url' => $current->store_url ?: $this->defaultStoreUrl($platform),
            'update_message' => $current->update_message ?: config('app-version-manager.defaults.update_message'),
            'is_active' => true,
        ]);

        event(new StoreVersionReleased($created, $live, $previous, 'promoted'));
        $this->notify($platform, $live, $previous);

        return "{$platform}: released {$live->version} (build {$previous} → {$live->build})";
    }

    private function defaultStoreUrl(string $platform): string
    {
        if ($platform === Platform::IOS->value) {
            $appId = config('app-version-manager.stores.appstore.app_id') ?: config('services.appstore.app_id');

            return $appId ? "https://apps.apple.com/app/id{$appId}" : 'https://apps.apple.com';
        }

        $package = config('app-version-manager.stores.googleplay.package_name') ?: config('services.googleplay.package_name');

        return $package ? "https://play.google.com/store/apps/details?id={$package}" : 'https://play.google.com';
    }

    private function notify(string $platform, StoreVersion $live, int $previousBuild): void
    {
        $recipient = config('app-version-manager.notifications.alert_email');
        if (blank($recipient)) {
            return;
        }

        try {
            Mail::raw(
                ucfirst($platform)." is now live at {$live->version} (build {$live->build}, was {$previousBuild}). "
                    .'The in-app latest-version policy has been updated automatically.',
                fn ($m) => $m->to($recipient)->subject("🚀 {$platform} released: {$live->version}"),
            );
        } catch (\Throwable $e) {
            Log::error('Failed to send store-release alert', [
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
