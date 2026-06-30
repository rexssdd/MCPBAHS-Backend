<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * StorageUploader
 *
 * Wraps Storage::disk(...)->putFileAs() with a safety net: if the
 * configured default disk (e.g. FILESYSTEM_DISK=s3 per .env.example)
 * isn't actually reachable — missing/invalid S3 credentials being the
 * most common cause on a fresh Railway deploy — uploads used to bubble
 * up as an uncaught exception and return a bare 500 to the client
 * (this is what was happening on POST /v1/reports and TVL offer image
 * uploads). This helper tries the configured disk first and silently
 * falls back to the local 'public' disk (always available, no external
 * credentials needed) so uploads keep working even before object
 * storage is fully wired up in production.
 *
 * Note: files written to the 'public' fallback disk live on Railway's
 * ephemeral container filesystem and will NOT survive a redeploy —
 * configuring real S3/R2 credentials is still the durable fix. This
 * just prevents a misconfiguration from taking the whole feature down.
 */
class StorageUploader
{
    /**
     * @return array{path: string, disk: string, url: string}
     */
    public static function store(UploadedFile $file, string $directory, string $filename): array
    {
        $preferredDisk = config('filesystems.default', 'local');

        foreach (array_unique([$preferredDisk, 'public']) as $disk) {
            try {
                $path = $file->storeAs($directory, $filename, $disk);

                if ($path === false) {
                    throw new \RuntimeException("Storage::disk('{$disk}')->storeAs() returned false.");
                }

                $diskInstance = Storage::disk($disk);
                /** @var \Illuminate\Filesystem\FilesystemAdapter $diskInstance */

                return [
                    'path' => $path,
                    'disk' => $disk,
                    'url'  => $diskInstance->url($path),
                ];
            } catch (Throwable $e) {
                Log::warning("StorageUploader: failed to store file on disk [{$disk}], trying fallback.", [
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        throw new \RuntimeException('Unable to store uploaded file on any available disk.');
    }
}