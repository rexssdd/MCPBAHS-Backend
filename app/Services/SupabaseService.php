<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SupabaseService
{
    private $url;
    private $key;

    public function __construct()
    {
        $this->url = env('SUPABASE_URL');
        $this->key = env('SUPABASE_SERVICE_ROLE_KEY');
    }

    public function getUsers()
{
    $response = Http::withHeaders([
        'apikey' => $this->key,
        'Authorization' => 'Bearer ' . $this->key,
    ])->get($this->url . '/rest/v1/users?select=*');

    if ($response->failed()) {
        return [
            'error' => true,
            'message' => $response->body()
        ];
    }

    return $response->json();
}
}