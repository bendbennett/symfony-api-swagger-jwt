<?php
namespace App\Service;

use App\Document\User;
use App\Document\UserCompany;

interface SerializerServiceInterface
{
    public function serializeUser(User $user, string $format) : string;

    public function serializeUsers(array $users, string $format) : string;

    public function serializeUserCompany(UserCompany $user, string $format) : string;

    public function deserializeUserFromJson(string $dataToDeserialize, string $dataToDeserializeFormat, string $id = null) : User;

    public function deserializeUserCompanyFromJson(string $dataToDeserialize, string $dataToDeserializeFormat, string $companyId = null) : UserCompany;
}
