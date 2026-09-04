<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class JWT extends BaseConfig
{
    public string $secret;

    public string $algorithm = 'HS256';

    public int $expiration;

    public string $issuer = 'bingo-api';

    public string $audience = 'bingo-app';

    public function __construct()
    {
        parent::__construct();

        $this->secret = (string) env('JWT_SECRET', '');

        $this->expiration = (int) env('JWT_EXPIRATION', 3600);

        if ($this->secret === '') {
            throw new \RuntimeException(
                'JWT_SECRET is not configured in .env'
            );
        }
    }
}