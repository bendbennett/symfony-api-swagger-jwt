<?php

namespace App\Manager;

use App\Document\User;
use App\Document\UserCompany;
use Doctrine\Common\EventManager;
use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserManager extends DocumentManager
{
    const USER_REPOSITORY = 'App:User';

    /**
     * @var \Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface
     */
    protected $passwordEncoder;

    public function __construct(
        Connection $conn = null,
        Configuration $config = null,
        EventManager $eventManager = null,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        $this->passwordEncoder = $passwordEncoder;
        parent::__construct($conn, $config, $eventManager);
    }

    public function encodePassword(User $user, string $password): User
    {
        $password = $this->passwordEncoder->encodePassword($user, $password);
        $user->setPassword($password);

        return $user;
    }

    public function isPasswordValid(User $user, string $password): bool
    {
        return $this->passwordEncoder->isPasswordValid($user, $password);
    }

    public function getAllUsers(): array
    {
        return $this->getRepository(self::USER_REPOSITORY)->findAll();
    }

    public function getUser(string $id): User
    {
        return $this->getRepository(self::USER_REPOSITORY)->find($id);
    }

    public function getUserByIdAndCompanyId(string $id, string $companyId): User
    {
        return $this->getRepository(self::USER_REPOSITORY)->getUserByIdAndCompanyId($id, $companyId);
    }

    public function getUserByEmailAndCompanyId(string $email, string $companyId): User
    {
        return $this->getRepository(self::USER_REPOSITORY)->getUserByEmailAndCompanyId($email, $companyId);
    }

    public function getUserByKeyValue(array $criteria): User
    {
        return $this->getRepository(self::USER_REPOSITORY)->findOneBy($criteria);
    }

    public function storeUser(User $user): void
    {
        $this->persist($user);
        $this->flush();
    }

    public function updateUserCompany(string $id, string $companyId, UserCompany $userCompany): User
    {
        return $this->getRepository(self::USER_REPOSITORY)->updateUserCompany($id, $companyId, $userCompany);
    }

    public function removeUser(User $user): void
    {
        $this->remove($user);
        $this->flush();
    }
}
