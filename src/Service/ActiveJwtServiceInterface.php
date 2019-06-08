<?php

namespace App\Service;

interface ActiveJwtServiceInterface
{
    public function getPayload(): array;

    public function getPayloadRoles(): array;

    public function getPayloadId(): string;
}
