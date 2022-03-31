<?php

namespace App\Directus;

use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class DirectusUserProvider implements UserProvider
{
    /**
     * The session used by the guard.
     *
     * @var \Illuminate\Contracts\Session\Session
     */
    protected $session;

    /**
     * Create a new authentication guard.
     *
     * @param  \Illuminate\Contracts\Session\Session  $session
     * @return void
     */
    public function __construct($session)
    {
        $this->session = $session;
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($token)
    {
        try {
            return app('directus')->getUser($token);
        } catch (\Exception $e) {
            Debugbar::error($e);
            return null;
        }
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $token
     * @param  string  $refreshToken
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($token, $refreshToken)
    {
        try {
            $token = app('directus')->refreshToken($refreshToken);
            if ($token != null) {
                $this->updateSession($token);
                return app('directus')->getUser($token['access_token']);
            }
        } catch (\Exception $e) {
            Debugbar::error($e);
            return null;
        }
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        //
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials) ||
           !isset($credentials['email'])) {
            return;
        }

        $user = new DirectusUser();
        $user->email = $credentials['email'];

        return $user;
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        if (empty($credentials) ||
           !isset($credentials['password'])) {
            return false;
        }

        try {
            $token = app('directus')->getToken($user->email, $credentials['password']);
            if ($token != null) {
                $this->updateSession($token);
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function updateSession($token)
    {
        $this->session->put('access_token', $token['access_token']);
        $this->session->put('token_expires', time() + ($token['expires'] / 1000));
        $this->session->put('refresh_token', $token['refresh_token']);

        $this->session->migrate(true);
    }
}
