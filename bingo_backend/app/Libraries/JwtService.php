<?php

namespace App\Libraries;

use Config\JWT;
use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;
use Throwable;

class JwtService
{
    private JWT $config;

    public function __construct()
    {
        $this->config = new JWT();
    }

    /**
     * Generate an access token for a user.
     */
    public function generateToken(array $user): string
    {
        $issuedAt = time();
        $expiration = $issuedAt + $this->config->expiration;

        $payload = [
            'iss' => $this->config->issuer,
            'aud' => $this->config->audience,

            'iat' => $issuedAt,
            'nbf' => $issuedAt,
            'exp' => $expiration,

            'sub' => (string) $user['id'],

            'user_id' => (int) $user['id'],
            'telegram_username' => $user['telegram_username'],
        ];

        return FirebaseJWT::encode(
            $payload,
            $this->config->secret,
            $this->config->algorithm
        );
    }

    /**
     * Decode and verify an access token.
     */
    public function validateToken(string $token): ?array
    {
        try {
            $decoded = FirebaseJWT::decode(
                $token,
                new Key(
                    $this->config->secret,
                    $this->config->algorithm
                )
            );

            return (array) $decoded;
        } catch (Throwable $e) {
            log_message(
                'warning',
                'JWT validation failed: ' . $e->getMessage()
            );

            return null;
        }
    }

    /**
     * Return token expiration time.
     */
    public function getExpiration(): int
    {
        return $this->config->expiration;
    }
}