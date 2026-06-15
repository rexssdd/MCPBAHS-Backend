<?php

namespace App\Actions\Reports;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class StoreReportAction
{
    public function execute(UploadedFile $file, string $uuid): array
    {
        $extension = $file->getClientOriginalExtension();

        $directory = 'reports';

        // ✅ Ensure folder exists in storage/app/reports
        if (!Storage::disk('local')->exists($directory)) {
            Storage::disk('local')->makeDirectory($directory);
        }

        $path = $file->storeAs(
            $directory,
            "{$uuid}.{$extension}",
            'local'
        );

        return [
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ];
    }
}