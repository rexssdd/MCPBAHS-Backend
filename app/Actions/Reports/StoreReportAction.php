<?php

namespace App\Actions\Reports;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StoreReportAction
{
    public function execute(UploadedFile $file, string $uuid): array
    {
        $extension = $file->getClientOriginalExtension();
        $directory = 'reports';

        // Uses the disk configured via FILESYSTEM_DISK env var.
        // In development this is 'local'; in production on Railway
        // it should be 's3' (or any S3-compatible driver like R2)
        // so that files survive container restarts and redeploys.
        $path = $file->storeAs(
            $directory,
            "{$uuid}.{$extension}",
            Storage::getDefaultDriver()
        );

        return [
            'file_path'         => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type'         => $file->getMimeType(),
            'file_size'         => $file->getSize(),
        ];
    }
}
