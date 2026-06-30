<?php

namespace App\Actions\Reports;

use App\Support\StorageUploader;
use Illuminate\Http\UploadedFile;

class StoreReportAction
{
    public function execute(UploadedFile $file, string $uuid): array
    {
        $extension = $file->getClientOriginalExtension();
        $directory = 'reports';

        // StorageUploader tries the configured default disk (e.g. S3 in
        // production) and automatically falls back to the local 'public'
        // disk if that disk isn't actually reachable (e.g. missing S3
        // credentials), instead of letting the whole request 500.
        $stored = StorageUploader::store($file, $directory, "{$uuid}.{$extension}");

        return [
            'file_path'         => $stored['path'],
            'original_filename' => $file->getClientOriginalName(),
            'mime_type'         => $file->getMimeType(),
            'file_size'         => $file->getSize(),
        ];
    }
}