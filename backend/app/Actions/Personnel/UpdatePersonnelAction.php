<?php

namespace App\Actions\Personnel;

use App\Models\Personnel;

class UpdatePersonnelAction
{
    public function execute(Personnel $personnel, array $data): Personnel
    {
        $personnel->update($data);

        return $personnel->refresh();
    }
}
