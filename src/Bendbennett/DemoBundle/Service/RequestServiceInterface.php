<?php
namespace Bendbennett\DemoBundle\Service;

interface RequestServiceInterface
{
    public function verifyOrAddIdToRequest(string $dataToDeserialize, string $id, string $idName) : string;
}