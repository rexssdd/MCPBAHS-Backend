<?php

namespace App\Actions\Reports;

use Illuminate\Support\Facades\Storage;

class DeleteReportAction
{
    public function execute(string $filePath): void
    {
        Storage::disk('local')->delete($filePath);
    }
}
