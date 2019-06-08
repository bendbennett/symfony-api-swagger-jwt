<?php

namespace App\Service;

use App\Document\User;
use App\Manager\UserManager;
use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Symfony\Component\Security\Core\Exception\InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthenticationService implements AuthenticationServiceInterface
{
    const USER_REPOSITORY = 'App:User';

    /**
     * @var UserManager;
     */
    protected $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    public function login(string $email, string $password): User
    {
        try {
            $user = $this->userManager->getUserByKeyValue(['email' => $email]);
        } catch (DocumentNotFoundException $e) {
            throw new InvalidArgumentException("User with email = $email does not exist.", 0, $e);
        }

        $this->validatePassword($user, $password);

        return $user;
    }

    public function loginToCompany($email, $password, $companyId): User
    {
        try {
            $user = $this->userManager->getUserByEmailAndCompanyId($email, $companyId);
        } catch (DocumentNotFoundException $e) {
            throw new InvalidArgumentException("User with email = $email and companyId = $companyId does not exist.", 0, $e);
        }

        $this->validatePassword($user, $password);

        return $user;
    }

    private function validatePassword(User $user, string $password): bool
    {
        if (!$this->userManager->isPasswordValid($user, $password)) {
            throw new UnauthorizedHttpException('Password is invalid.');
        }

        return true;
    }
}
