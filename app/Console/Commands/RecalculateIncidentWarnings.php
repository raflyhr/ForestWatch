<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:recalculate-incident-warnings')]
#[Description('Command description')]
class RecalculateIncidentWarnings extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(\App\Services\WarningEngine $warningEngine)
    {
        $this->info("Memproses insiden dengan warning_level NULL...");

        \App\Models\Incident::whereNull('warning_level')->chunkById(100, function ($incidents) use ($warningEngine) {
            foreach ($incidents as $incident) {
                $warningEngine->recalculate($incident);
            }
            $this->info("Processed 100 incidents...");
        });

        $this->info("Recalculate selesai. Seluruh data real telah diproses.");
    }
}
