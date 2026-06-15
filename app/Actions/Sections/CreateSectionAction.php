<?php

namespace App\Actions\Sections;

use App\Models\Section;

class CreateSectionAction
{
    public function execute(array $data): Section
    {
        return Section::create($data);
    }
}
