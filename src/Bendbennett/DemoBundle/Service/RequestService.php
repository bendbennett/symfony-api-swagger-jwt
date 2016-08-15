<?php

namespace Bendbennett\DemoBundle\Service;

use Symfony\Component\Security\Core\Exception\InvalidArgumentException;

class RequestService implements RequestServiceInterface
{
    public function verifyOrAddIdToRequest(string $dataToDeserialize, string $id, string $idName) : string
    {
        $jsonAsObject = $this->convertJsonToObject($dataToDeserialize);

        if (!isset($jsonAsObject->$idName)) {
            $jsonAsObject->$idName = $id;
            return json_encode($jsonAsObject);
        } else {
            if ($jsonAsObject->$idName != $id) {
                throw new InvalidArgumentException(sprintf("id (id = '%s') submitted in query string does not match id (id = '%s') in request body", $id, $jsonAsObject->$idName));
            }
        }

        return json_encode($jsonAsObject);
    }

    private function convertJsonToObject(string $json) : \stdClass
    {
        $jsonAsObject = json_decode($json);

        if (!isset($jsonAsObject) || !$jsonAsObject) {
            if ($error = json_last_error_msg()) {
                throw new InvalidArgumentException(sprintf("Failed to parse json string '%s', error: '%s'", $json, $error));
            }
        }

        return $jsonAsObject;
    }
}
