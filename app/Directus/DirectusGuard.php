<?php

namespace App\Directus;

use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Session\Session;

class DirectusGuard implements Guard
{
    use GuardHelpers;

    protected $token;

    /**
     * The user we last attempted to retrieve.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    protected $lastAttempted;

    /**
     * The session used by the guard.
     *
     * @var \Illuminate\Contracts\Session\Session
     */
    protected $session;

    public function __construct(UserProvider $provider, Session $session)
    {
        $this->provider = $provider;
        $this->session = $session;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        if (! is_null($this->user)) {
            return $this->user;
        }

        $token = $this->token();

        if (! is_null($token) && $this->user = $this->provider->retrieveById($token)) {
            // $this->fireAuthenticatedEvent($this->user);
        }

        if (is_null($this->user) && ! is_null($refreshToken = $this->refreshToken())) {
            $this->user = $this->provider->retrieveByToken($token, $refreshToken);

            // if ($this->user) {
            //     $this->fireLoginEvent($this->user, true);
            // }
            if (is_null($this->user)) {
                $this->logout();
            } else {
                return redirect()->refresh();
            }
        }

        // Debugbar::log($this->user);

        return $this->user;
    }

    public function token()
    {
        if (! is_null($this->token)) {
            return $this->token;
        }

        return $this->token = $this->session->get('access_token');
    }

    public function refreshToken()
    {
        return $this->session->get('refresh_token');
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);

        return $this->hasValidCredentials($user, $credentials);
    }

    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param  array  $credentials
     * @param  bool  $remember
     * @return bool
     */
    public function attempt(array $credentials = [], $remember = false)
    {
        // $this->fireAttemptEvent($credentials, $remember);

        $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);

        if ($this->hasValidCredentials($user, $credentials)) {
            $this->login($user, $remember);

            return true;
        }

        // $this->fireFailedEvent($user, $credentials);

        return false;
    }

    /**
     * Determine if the user matches the credentials.
     *
     * @param  mixed  $user
     * @param  array  $credentials
     * @return bool
     */
    protected function hasValidCredentials($user, $credentials)
    {
        $validated = ! is_null($user) && $this->provider->validateCredentials($user, $credentials);

        if ($validated) {
            // $this->fireValidatedEvent($user);
        }

        return $validated;
    }
    /**
     * Log a user into the application.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  bool  $remember
     * @return void
     */
    public function login(Authenticatable $user, $remember = false)
    {
        // $this->updateSession($user->getAuthIdentifier());

        // if ($remember) {
        //     $this->ensureRememberTokenIsSet($user);

        //     $this->queueRecallerCookie($user);
        // }

        // $this->fireLoginEvent($user, $remember);

        $this->setUser($user);
    }

    public function logout()
    {
        // $user = $this->user();

        $this->clearUserDataFromStorage();

        $this->user = null;
        $this->token = null;

        // if (isset($this->events)) {
        //     $this->events->dispatch(new Logout($this->name, $user));
        // }
    }


    protected function clearUserDataFromStorage()
    {
        $this->session->remove('access_token');
        $this->session->remove('token_expires');
        $this->session->remove('refresh_token');
    }
}
