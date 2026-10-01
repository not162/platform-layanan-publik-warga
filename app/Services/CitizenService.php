<?php

namespace App\Services;

use App\Models\Citizen;

class CitizenService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function create(array $data): Citizen
    {
        $data['nik_hash'] = hash('sha256', $data['nik']);

        return Citizen::create($data);
    }

    public function update(Citizen $citizen, array $data): Citizen
    {
        if (isset($data['nik'])) {
            $data['nik_hash'] = hash('sha256', $data['nik']);
        }

        if (isset($data['version'])) {
            if ((int) $data['version'] !== (int) $citizen->version) {
                abort(409, 'Conflict: Citizen data has been updated by another user. Please refresh and try again.');
            }
            $data['version'] = (int) $citizen->version + 1;
        }

        $citizen->update($data);

        return $citizen;
    }
}
