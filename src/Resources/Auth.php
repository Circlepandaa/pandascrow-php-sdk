<?php

namespace Pandascrow\Resources;

class Auth extends Resource
{
    /** Returns ['token' => ..., 'user' => ...] or ['otp_required' => true, ...] for MFA users. */
    public function login(string $email, string $password): array
    {
        return $this->client->post('/login', ['email' => $email, 'password' => $password]);
    }
}
