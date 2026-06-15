<?php

namespace App\Actions\Reports;

use App\Models\Report;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class CreateReportAction
{
    public function __construct(
        private StoreReportAction $storeFile
    ) {
        //
    }

        public function execute(array $data, UploadedFile $file, User $user): Report
        {
            $uuid = (string) Str::uuid();

            $fileData = $this->storeFile->execute($file, $uuid);

            return Report::create([
                'form_type'   => $data['form_type'],
                'school_year' => $data['school_year'],

                // MUST come from store action
                'file_path'        => $fileData['file_path'],
                'original_filename'=> $fileData['original_filename'],

                'submitted_by' => $user->id,
            ]);
        }
}
