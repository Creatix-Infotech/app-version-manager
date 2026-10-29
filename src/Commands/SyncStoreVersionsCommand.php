<?php

namespace Creatix\AppVersionManager\Commands;

use Creatix\AppVersionManager\Services\Store\AppStoreConnectClient;
use Creatix\AppVersionManager\Services\Store\GooglePlayClient;
use Creatix\AppVersionManager\Services\StoreVersionSyncer;
use Illuminate\Console\Command;

class SyncStoreVersionsCommand extends Command
{
    protected $signature = 'app-versions:sync';

    protected $description = 'Fetch the live published builds from Google Play & App Store Connect and update active version policies';

    public function handle(
        AppStoreConnectClient $appStore,
        GooglePlayClient $googlePlay,
    ): int {
        $this->info('Starting store version synchronization...');

        $syncer = new StoreVersionSyncer([$appStore, $googlePlay]);

        foreach ($syncer->sync() as $line) {
            $this->line("  > {$line}");
        }

        $this->info('Store sync completed.');

        return self::SUCCESS;
    }
}

