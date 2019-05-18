<?php

namespace Tests\Bendbennett\DemoBundle\Controller;

class UsersControllerTest extends AbstractController
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @test
     * @group UsersController::indexAction
     */
    public function itShouldReturn200AndAllUsers()
    {
        $userOnePassword = 'passwordOne';

        $userOne = $this->loadUser('userOne@companyOne.com', $userOnePassword, 'abc123', ['Administrator']);
        $this->loadUser('userTwo@companyTwo.com', 'passwordTwo', 'xyz789', ['Director']);

        $client = self::createClient();
        $jwt = $this->login($client, $userOne->getEmail(), $userOnePassword);

        $client->request(
            'GET',
            '/users',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => "Bearer $jwt")
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $responseAsArray = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(2, $responseAsArray);
    }

    /**
     * @test
     * @group UsersController::indexAction
     */
    public function itShouldReturn403WhenJwtRoleIsNotAdministrator()
    {
        $userOnePassword = 'passwordOne';
        $userOne = $this->loadUser('userOne@companyOne.com', $userOnePassword, 'abc123', ['Director']);

        $client = self::createClient();
        $jwt = $this->login($client, $userOne->getEmail(), $userOnePassword);

        $client->request(
            'GET',
            '/users',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => "Bearer $jwt")
        );

        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     * @group UsersController::showAction
     */
    public function itShouldReturn200AndUserWhenLoggedInUserIsAdministrator()
    {
        $userOnePassword = 'passwordOne';
        $userOne = $this->loadUser('userOne@companyOne.com', $userOnePassword, 'abc123', ['Administrator']);

        $userTwoEmail = 'userTwo@companyTwo.com';
        $userTwoCompanyOneId = 'xyz789';
        $userTwo = $this->loadUser($userTwoEmail, 'passwordTwo', $userTwoCompanyOneId, ['User']);

        $client = self::createClient();
        $jwt = $this->login($client, $userOne->getEmail(), $userOnePassword);

        $client->request(
            'GET',
            '/users/' . $userTwo->getId(),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => "Bearer $jwt")
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $user = json_decode($client->getResponse()->getContent());

        $this->assertEquals($userTwo->getEmail(), $user->email);
        $this->assertEquals($userTwoCompanyOneId, $user->userCompanies[0]->companyId);
    }

    /**
     * @test
     * @group UsersController::showAction
     */
    public function itShouldReturn200AndUserWhenLoggedInUserIsDirectorInCompany()
    {
        $userOneEmail = 'userOne@companyOne.com';
        $userOnePassword = 'passwordOne';
        $userOne = $this->loadUser($userOneEmail, $userOnePassword, 'abc123', ['Director']);

        $userTwoEmail = 'userTwo@companyOne.com';
        $userTwoCompanyOneId = 'abc123';
        $userTwo = $this->loadUser($userTwoEmail, 'passwordTwo', $userTwoCompanyOneId, ['User']);

        $client = self::createClient();
        $jwt = $this->login($client, $userOne->getEmail(), $userOnePassword);

        $client->request(
            'GET',
            '/users/' . $userTwo->getId(),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => "Bearer $jwt")
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $user = json_decode($client->getResponse()->getContent());

        $this->assertEquals($userTwoEmail, $user->email);
        $this->assertEquals($userTwoCompanyOneId, $user->userCompanies[0]->companyId);
    }

    /**
     * @test
     * @group UsersController::showAction
     */
    public function itShouldReturn200AndUserWhenLoggedInUserIsUserInRequest()
    {
        $userOneEmail = 'userOne@companyOne.com';
        $userOnePassword = 'passwordOne';
        $userOneCompanyOneId = 'abc123';
        $userOne = $this->loadUser($userOneEmail, $userOnePassword, $userOneCompanyOneId, ['User']);

        $client = self::createClient();
        $jwt = $this->login($client, $userOne->getEmail(), $userOnePassword);

        $client->request(
            'GET',
            '/users/' . $userOne->getId(),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => "Bearer $jwt")
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $user = json_decode($client->getResponse()->getContent());

        $this->assertEquals($userOneEmail, $user->email);
        $this->assertEquals($userOneCompanyOneId, $user->userCompanies[0]->companyId);
    }

    /**
     * @test
     * @group UsersController::showAction
     */
    public function itShouldReturn403WhenLoggedInUserIsDirectorInDifferentCompany()
    {
        $userOne = $this->loadUser('userOne@companyOne.com', 'passwordOne', 'abc123', ['Administrator']);
        $userTwo = $this->loadUser('userTwo@companyTwo.com', 'passwordTwo', 'def789', ['Director']);

        $client = self::createClient();
        $jwt = $this->login($client, $userTwo->getEmail(), 'passwordTwo');

        $client->request(
            'GET',
            '/users/' . $userOne->getId(),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => "Bearer $jwt")
        );

        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     * @group UsersController::searchAction
     */
    public function itShouldReturn200AndUserWhenLoggedInUserIsAdministratorForSearch()
    {
        $userOnePassword = 'passwordOne';
        $userOne = $this->loadUser('userOne@companyOne.com', $userOnePassword, 'abc123', ['Administrator']);

        $userTwoEmail = 'userTwo@companyTwo.com';
        $userTwoCompanyOneId = 'xyz789';
        $userTwo = $this->loadUser($userTwoEmail, 'passwordTwo', $userTwoCompanyOneId, ['User']);

        $client = self::createClient();
        $jwt = $this->login($client, $userOne->getEmail(), $userOnePassword);

        $client->request(
            'GET',
            '/users/email/' . $userTwo->getEmail(),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => "Bearer $jwt")
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $user = json_decode($client->getResponse()->getContent());

        $this->assertEquals($userTwo->getEmail(), $user->email);
        $this->assertEquals($userTwoCompanyOneId, $user->userCompanies[0]->companyId);
    }

    /**
     * @test
     * @group UsersController::searchAction
     */
    public function itShouldReturn403WhenLoggedInUserIsNotAdministratorForSearch()
    {
        $userOne = $this->loadUser('userOne@companyOne.com', 'passwordOne', 'abc123', ['Administrator']);
        $userTwo = $this->loadUser('userTwo@companyTwo.com', 'passwordTwo', 'def789', ['Director']);

        $client = self::createClient();
        $jwt = $this->login($client, $userTwo->getEmail(), 'passwordTwo');

        $client->request(
            'GET',
            '/users/email/' . $userOne->getEmail(),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => "Bearer $jwt")
        );

        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     * @group UsersController::storeAction
     */
    public function itShouldReturn200AndUserWhenLoggedInUserIsAdministratorForStore()
    {
        $userOnePassword = 'passwordOne';
        $userOne = $this->loadUser('userOne@companyOne.com', $userOnePassword, 'abc123', ['Administrator']);

        $client = self::createClient();
        $jwt = $this->login($client, $userOne->getEmail(), $userOnePassword);

        $userTwoEmail = 'userTwo@companyTwo.com';
        $userTwoCompanyOneId = 'xyz798';

        $client->request(
            'POST',
            '/users',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => "Bearer $jwt"),
            json_encode([
                'email' => $userTwoEmail,
                'password' => 'passwordTwo',
                'userCompanies' => [
                    [
                        'companyId' => $userTwoCompanyOneId,
                        'roles' => ['Director']
                    ]
                ]
            ])
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $user = json_decode($client->getResponse()->getContent());

        $this->assertEquals($userTwoEmail, $user->email);
        $this->assertEquals($userTwoCompanyOneId, $user->userCompanies[0]->companyId);
    }

    /**
     * @test
     * @group UsersController::storeAction
     */
    public function itShouldReturn200AndUserWhenLoggedInUserIsDirectorInSameCompanyForStoreAction()
    {
        $userOnePassword = 'passwordOne';
        $userOne = $this->loadUser('userOne@companyOne.com', $userOnePassword, 'abc123', ['Director']);

        $client = self::createClient();
        $jwt = $this->login($client, $userOne->getEmail(), $userOnePassword);

        $userTwoEmail = 'userTwo@companyOne.com';
        $userTwoCompanyOneId = 'abc123';

        $client->request(
            'POST',
            '/users',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => "Bearer $jwt"),
            json_encode([
                'email' => $userTwoEmail,
                'password' => 'passwordTwo',
                'userCompanies' => [
                    [
                        'companyId' => $userTwoCompanyOneId,
                        'roles' => ['Director']
                    ]
                ]
            ])
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $user = json_decode($client->getResponse()->getContent());

        $this->assertEquals($userTwoEmail, $user->email);
        $this->assertEquals($userTwoCompanyOneId, $user->userCompanies[0]->companyId);
    }

    /**
     * @test
     * @group UsersController::storeAction
     */
    public function itShouldReturn403WhenLoggedInUserIsDirectorInDifferentCompanyForStoreAction()
    {
        $userOnePassword = 'passwordOne';
        $userOne = $this->loadUser('userOne@companyOne.com', $userOnePassword, 'abc123', ['Director']);

        $client = self::createClient();
        $jwt = $this->login($client, $userOne->getEmail(), $userOnePassword);

        $userTwoEmail = 'userTwo@companyTwo.com';
        $userTwoCompanyOneId = 'xyz789';

        $client->request(
            'POST',
            '/users',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => "Bearer $jwt"),
            json_encode([
                'email' => $userTwoEmail,
                'password' => 'passwordTwo',
                'userCompanies' => [
                    [
                        'companyId' => $userTwoCompanyOneId,
                        'roles' => ['Director']
                    ]
                ]
            ])
        );

        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     * @group UsersController::editAction
     */
    public function itShouldReturn200AndUserForEditAction()
    {
        $userOne = $this->loadUser('userOne@companyOne.com', 'passwordOne', 'abc123', ['Director']);

        $client = self::createClient();
        $jwt = $this->login($client, $userOne->getEmail(), 'passwordOne');

        $userOneNewPassword = 'passwordTwo';
        $userOneNewEmail = 'newemail@companyOne.com';

        $client = self::createClient();
        $client->request(
            'PATCH',
            '/users/' . $userOne->getId(),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => "Bearer $jwt"),
            json_encode([
                'email' => $userOneNewEmail,
                'password' => $userOneNewPassword
            ])
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $serializerService = $this->bootedTestKernel->getContainer()->get('Bendbennett\DemoBundle\Service\SerializerService');
        $userManager = $this->bootedTestKernel->getContainer()->get('Bendbennett\DemoBundle\Manager\UserManager');

        $user = $serializerService->deserializeUserFromJson($client->getResponse()->getContent(), 'json', $userOne->getId());
        $userFromDb = $userManager->getUser($userOne->getId());

        $this->assertEquals($userOneNewEmail, $user->getEmail());
        $this->assertTrue($userManager->isPasswordValid($userFromDb, $userOneNewPassword));
    }

    /**
     * @test
     * @group UsersController::deleteAction
     * @expectedException \Doctrine\ODM\MongoDB\DocumentNotFoundException
     */
    public function itShouldReturn200ForDeleteAction()
    {
        $userOne = $this->loadUser('userOne@companyOne.com', 'passwordOne', 'abc123', ['Director']);

        $client = self::createClient();
        $jwt = $this->login($client, $userOne->getEmail(), 'passwordOne');

        $client = self::createClient();
        $client->request(
            'DELETE',
            '/users/' . $userOne->getId(),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => "Bearer $jwt")
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $userManager = $this->bootedTestKernel->getContainer()->get('Bendbennett\DemoBundle\Manager\UserManager');
        $userManager->getUser($userOne->getId());
    }
}
