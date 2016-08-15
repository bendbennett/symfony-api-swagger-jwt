<?php

namespace Bendbennett\DemoBundle\Service;

use Bendbennett\DemoBundle\Document\User;

interface AuthenticationServiceInterface
{
    public function login(string $email, string $password) : User;

    public function loginToCompany($email, $password, $companyId) : User;
}
