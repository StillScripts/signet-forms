<?php

namespace App\Console\Commands;

use App\Models\SubmissionExport;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('exports:clean')]
#[Description('Delete expired submission export files and their records')]
class CleanupExpiredExports extends Command
{
    public function handle(): int
    {
        $disk = Storage::disk('local');
        $removedExports = 0;
        $removedFiles = 0;

        SubmissionExport::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->lazyById(500)
            ->each(function ($export) use ($disk, &$removedExports, &$removedFiles): void {
                if ($export->file_path && $disk->delete($export->file_path)) {
                    $removedFiles++;
                }
                $export->delete();
                $removedExports++;
            });

        $this->info("Removed {$removedExports} expired exports ({$removedFiles} files).");

        return self::SUCCESS;
    }
}
