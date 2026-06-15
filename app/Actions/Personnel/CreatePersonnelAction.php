<?php

namespace App\Actions\Personnel;

use App\Models\Personnel;

class CreatePersonnelAction
{
    public function execute(array $data): Personnel
    {
        return Personnel::create($data);
    }
}
