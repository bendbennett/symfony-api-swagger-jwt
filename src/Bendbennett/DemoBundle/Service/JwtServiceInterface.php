<?php

namespace Bendbennett\DemoBundle\Service;

interface JwtServiceInterface
{
    public function generateJwt(string $userId, array $customClaims) : string;
}
