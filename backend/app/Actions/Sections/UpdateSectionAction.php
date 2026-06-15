<?php

namespace App\Actions\Sections;

use App\Models\Section;

class UpdateSectionAction
{
    public function execute(Section $section, array $data): Section {
        $section->update($data);

        return $section->refresh();
    }
}
