<?php

namespace App\Tests\Controller;

use App\Kernel;
use App\Document\User;
use App\Document\UserCompany;
use Doctrine\Common\DataFixtures\Purger\MongoDBPurger;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AbstractController extends WebTestCase
{
    /**
     * @var \Symfony\Component\HttpKernel\Kernel;
     */
    protected $bootedTestKernel;

    public function setUp(): void
    {
        parent::setUp();

        /* @link http://blog.sznapka.pl/fully-isolated-tests-in-symfony2/ */
        $kernel = new Kernel("test", true);
        $kernel->boot();

        $this->bootedTestKernel = $kernel;
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $purger = new MongoDBPurger($this->bootedTestKernel->getContainer()->get('doctrine_mongodb')->getManager());
        $purger->purge();
    }

    protected function loadUser(string $email, string $password, string $companyId = null, array $roles = null)
    {
        $securityPasswordEncoder = $this->bootedTestKernel->getContainer()->get('security.password_encoder');

        $user = new User();
        $user->setEmail($email);
        $password = $securityPasswordEncoder->encodePassword($user, $password);
        $user->setPassword($password);

        if (is_string($companyId)) {
            $this->addCompanyToUser($user, $companyId, $roles);
        }

        $this->getUserManager()->persist($user);
        $this->getUserManager()->flush();

        return $user;
    }

    protected function addCompanyToUser(User $user, string $companyId, array $roles)
    {
        $userCompany = new UserCompany();
        $userCompany->setCompanyId($companyId);
        $userCompany->setRoles($roles);

        $user->addUserCompany($userCompany);

        $this->getUserManager()->persist($user);
        $this->getUserManager()->flush();
    }

    protected function getClaimsFromJwt(string $jwt)
    {
        $explodedJwt = explode('.', $jwt);
        $jwtClaims = $explodedJwt[1];
        return json_decode(base64_decode($jwtClaims));
    }

    protected function login(KernelBrowser $client, string $email, string $password)
    {
        $client->request(
            'POST',
            '/login',
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode([
                'email' => $email,
                'password' => $password
            ])
        );

        $responseAsArray = json_decode($client->getResponse()->getContent(), true);

        return $responseAsArray['token'];
    }

    protected function getUserManager()
    {
        return $this->bootedTestKernel->getContainer()->get('doctrine_mongodb')->getManager();
    }

    protected function getUserRepository()
    {
        return $this->getUserManager()->getRepository('App:User');
    }
}
