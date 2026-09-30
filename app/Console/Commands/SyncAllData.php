<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('forestwatch:sync-all')]
#[Description('Orchestrate full data synchronization (NASA FIRMS, BMKG, and Risk Recalculation)')]
class SyncAllData extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Triggering asynchronous data synchronization...');

        // Trigger NASA fetch (new incidents will automatically fetch BMKG weather via IncidentObserver)
        $this->call('forestwatch:fetch-nasa');

        $this->info('Synchronization process (NASA) triggered. BMKG weather will be automatically fetched for new/updated incidents via Observer.');
    }
}
