<?php

namespace App\DataFixtures\MongoDB;

use App\Document\User;
use App\Document\UserCompany;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadUser implements FixtureInterface, ContainerAwareInterface
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $securityPasswordEncoder = $this->container->get('security.password_encoder');

        $user = new User();
        $user->setEmail('administrator@demo.com');
        $password = $securityPasswordEncoder->encodePassword($user, 'admin');
        $user->setPassword($password);

        $userCompany = new UserCompany();
        $userCompany->setCompanyId('abc123');
        $userCompany->setRoles(['Administrator']);

        $user->addUserCompany($userCompany);

        $userCompany = new UserCompany();
        $userCompany->setCompanyId('xyz789');
        $userCompany->setRoles(['Director']);

        $user->addUserCompany($userCompany);
        
        $manager->persist($user);
        $manager->flush();
    }
}
