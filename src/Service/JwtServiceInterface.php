<?php

namespace App\Service;

interface JwtServiceInterface
{
    public function generateJwt(string $userId, array $customClaims): string;
}
