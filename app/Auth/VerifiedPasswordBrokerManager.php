<?php

namespace App\Auth;

use Illuminate\Auth\Passwords\PasswordBrokerManager;

class VerifiedPasswordBrokerManager extends PasswordBrokerManager
{
    protected function resolve($name)
    {
        $config = $this->getConfig($name);
        if (is_null($config)) {
            throw new \InvalidArgumentException('Password broker no configurado.');
        }

        return new VerifiedPasswordBroker($this->createTokenRepository($config), $this->app['auth']->createUserProvider($config['provider'] ?? null), $this->app['events'] ?? null,
            timeboxDuration: $this->app['config']->get('auth.timebox_duration', 200000));
    }
}
