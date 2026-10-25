<?php

namespace Pandascrow\Resources;

/**
 * Dashboard-style login. Integrations normally only need the API key passed to
 * the Client; use this when you need a user's uuid from their email/password.
 */
class Auth extends Resource
{
    /**
     * Returns the user (including `uuid`), or a flag telling you an OTP / 2FA step is needed.
     *
     * @return array<string, mixed>
     */
    public function login(string $email, string $password): array
    {
        return $this->client->post('/login', ['email' => $email, 'password' => $password]);
    }

    /**
     * Second step when login returned an OTP requirement.
     *
     * @return array<string, mixed>
     */
    public function verifyLoginOtp(string $email, string $otp): array
    {
        return $this->client->post('/login/otp', ['email' => $email, 'otp_code' => $otp]);
    }
}
