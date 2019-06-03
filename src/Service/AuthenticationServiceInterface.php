<?php

namespace App\Service;

use App\Document\User;

interface AuthenticationServiceInterface
{
    public function login(string $email, string $password) : User;

    public function loginToCompany($email, $password, $companyId) : User;
}
