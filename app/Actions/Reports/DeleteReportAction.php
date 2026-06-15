<?php

namespace App\Actions\Reports;

use Illuminate\Support\Facades\Storage;

class DeleteReportAction
{
    public function execute(string $filePath): void
    {
        // Uses the disk configured via FILESYSTEM_DISK env var (same disk
        // that StoreReportAction used when the file was saved).
        Storage::delete($filePath);
    }
}
