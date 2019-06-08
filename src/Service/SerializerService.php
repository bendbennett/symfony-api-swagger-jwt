<?php

namespace App\Service;

use App\Document\User;
use App\Document\UserCompany;
use JMS\Serializer\SerializerInterface;

class SerializerService implements SerializerServiceInterface
{
    const USER = 'App\Document\User';
    const USER_COMPANY = 'App\Document\UserCompany';

    /**
     * @var \JMS\Serializer\SerializerInterface
     */
    protected $serializer;

    /**
     * @var RequestServiceInterface
     */
    protected $requestService;

    public function __construct(SerializerInterface $serializer, RequestServiceInterface $requestService)
    {
        $this->serializer = $serializer;
        $this->requestService = $requestService;
    }

    public function serializeUser(User $user, string $format): string
    {
        return $this->serialize($user, $format);
    }

    public function serializeUsers(array $users, string $format): string
    {
        return $this->serialize($users, $format);
    }

    public function serializeUserCompany(UserCompany $userCompany, string $format): string
    {
        return $this->serialize($userCompany, $format);
    }

    private function serialize($dataToSerialize, string $format): string
    {
        return $this->serializer->serialize($dataToSerialize, $format);
    }

    public function deserializeUserFromJson(string $dataToDeserialize, string $dataToDeserializeFormat, string $id = null): User
    {
        return $this->deserializeFromJson($dataToDeserialize, self::USER, $dataToDeserializeFormat, $id, 'id');
    }

    public function deserializeUserCompanyFromJson(string $dataToDeserialize, string $dataToDeserializeFormat, string $companyId = null): UserCompany
    {
        return $this->deserializeFromJson($dataToDeserialize, self::USER_COMPANY, $dataToDeserializeFormat, $companyId, 'companyId');
    }

    private function deserializeFromJson(string $dataToDeserialize, string $document, string $dataToDeserializeFormat, string $id = null, string $idName = null): Object
    {
        if ((is_string($id) || is_int($id)) && is_string($idName)) {
            $dataToDeserialize = $this->requestService->verifyOrAddIdToRequest($dataToDeserialize, $id, $idName);
        }

        return $this->serializer->deserialize($dataToDeserialize, $document, $dataToDeserializeFormat);
    }
}
