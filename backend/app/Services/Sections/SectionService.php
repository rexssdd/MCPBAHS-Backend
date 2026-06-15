<?php

namespace App\Services\Sections;

use App\Models\Section;

use Illuminate\Support\Facades\DB;

use App\Actions\Sections\CreateSectionAction;
use App\Actions\Sections\UpdateSectionAction;

class SectionService
{
    public function __construct(
        protected CreateSectionAction $createAction,
        protected UpdateSectionAction $updateAction,
        protected SectionValidationService $validationService,
    ) {
    }

    public function create(array $data): Section
    {
        return DB::transaction(function () use ($data) {

            $this->validationService->validate($data);

            return $this->createAction->execute($data);
        });
    }

    public function update(
        Section $section,
        array $data
    ): Section {
        return DB::transaction(function () use ($section, $data) {

            $merged = array_merge(
                $section->toArray(),
                $data
            );

            $this->validationService->validate(
                $merged,
                $section
            );

            return $this->updateAction->execute(
                $section,
                $data
            );
        });
    }

    public function delete(Section $section): void
    {
        $section->delete();
    }

    public function restore(Section $section): void
    {
        $section->restore();
    }

    public function forceDelete(Section $section): void
    {
        $section->forceDelete();
    }
}
