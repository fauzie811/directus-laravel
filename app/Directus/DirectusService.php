<?php

namespace App\Directus;

use Exception;
use Illuminate\Support\Facades\Http;

class DirectusService
{
    protected string $baseUrl = 'http://localhost:8055';

    public function __construct()
    {
        $this->baseUrl = config('directus.base_url');
    }

    protected function headers($token): array
    {
        return [
            "Authorization: Bearer $token",
        ];
    }

    public function getToken($email, $password)
    {
        $res = Http::post("{$this->baseUrl}/auth/login", compact('email', 'password'));

        $json = $res->json();
        if ($res->status() == 200) {
            return $json['data'];
        } else {
            throw new Exception($json['errors'][0]['message']);
        }
    }

    public function refreshToken($refreshToken)
    {
        $res = Http::post("{$this->baseUrl}/auth/refresh", [
            'refresh_token' => $refreshToken,
        ]);

        $json = $res->json();
        if ($res->status() == 200) {
            return $json['data'];
        } else {
            throw new Exception($json['errors'][0]['message']);
        }
    }

    public function getItems(string $token, string $collection): array
    {
        $res = Http::get($this->baseUrl . "/items/$collection", [
            'access_token' => $token,
        ]);

        $json = $res->json();
        if ($res->status() == 200) {
            return $json['data'];
        } else {
            throw new Exception($json['errors'][0]['message']);
        }
    }

    public function getItem(string $token, string $collection, $id): array
    {
        $res = Http::get($this->baseUrl . "/items/$collection/$id", [
            'access_token' => $token,
        ]);

        $json = $res->json();
        if ($res->status() == 200) {
            return $json['data'];
        } else {
            throw new Exception($json['errors'][0]['message']);
        }
    }

    public function updateItem($token, string $collection, $id, array $data = []): array
    {
        $res = Http::withHeaders($this->headers($token))
            ->patch("{$this->baseUrl}/items/$collection/$id", $data);

        $json = $res->json();
        if ($res->status() == 200) {
            return $json['data'];
        } else {
            throw new Exception($json['errors'][0]['message']);
        }
    }

    public function getUser($token): DirectusUser
    {
        $res = Http::get($this->baseUrl . "/users/me", [
            'access_token' => $token,
        ]);

        $json = $res->json();
        if ($res->status() == 200) {
            return DirectusUser::fromArray($json['data']);
        } else {
            throw new Exception($json['errors'][0]['message']);
        }
    }
}
