<?php

namespace Bendbennett\DemoBundle\Repository;

use Bendbennett\DemoBundle\Document\User;
use Bendbennett\DemoBundle\Document\UserCompany;
use Bendbennett\DemoBundle\Service\SerializerServiceInterface;
use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\LockMode;

class UserRepository extends DocumentRepository
{
    /**
     * @var SerializerServiceInterface
     */
    protected $serializerService;

    public function setSerializerService(SerializerServiceInterface $serializerService)
    {
        $this->serializerService = $serializerService;
    }

    public function getUserByIdAndCompanyId(string $id, string $companyId)
    {
        $user = $this->createQueryBuilder()
            ->field('_id')->equals($id)
            ->field('userCompanies.companyId')->equals($companyId)
            ->getQuery()
            ->getSingleResult();

        if (!$user instanceof User) {
            throw new DocumentNotFoundException(sprintf('User with _id = %s and companyId = %s could not be found.', $id, $companyId));
        }

        return $user;
    }

    public function getUserByEmailAndCompanyId(string $email, string $companyId)
    {
        $user = $this->createQueryBuilder()
            ->field('email')->equals($email)
            ->field('userCompanies.companyId')->equals($companyId)
            ->getQuery()
            ->getSingleResult();

        if (!$user instanceof User) {
            throw new DocumentNotFoundException(sprintf('User with email = %s and companyId = %s could not be found.', $email, $companyId));
        }

        return $user;
    }

    public function updateUserCompany(string $id, string $companyId, UserCompany $userCompany) : User
    {
        $user = $this->createQueryBuilder()
            ->findAndUpdate()
            ->returnNew()
            ->field('_id')->equals($id)
            ->field('userCompanies.companyId')->equals($companyId)
            ->field('userCompanies.$.roles')->set($userCompany->getRoles())
            ->getQuery()
            ->execute();

        if (!$user instanceof User) {
            throw new DocumentNotFoundException(sprintf('User with id = %s and companyId = %s could not be found.', $id, $companyId));
        }

        return $user;
    }

    public function find($id, $lockMode = LockMode::NONE, $lockVersion = null)
    {
        $user = parent::find($id, $lockMode, $lockVersion);

        if (!$user instanceof User) {
            throw new DocumentNotFoundException(sprintf('User with id = %s could not be found.', $id));
        }

        return $user;
    }

    public function findOneBy(array $criteria)
    {
        $user = parent::findOneBy($criteria);

        if (!$user instanceof User) {
            throw new DocumentNotFoundException(sprintf('User with criteria %s could not be found.', urldecode(http_build_query($criteria, '', ', '))));
        }

        return $user;
    }
}
