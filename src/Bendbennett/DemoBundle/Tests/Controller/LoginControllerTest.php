<?php

namespace Bendbennett\DemoBundle\Tests\Controller;

class LoginControllerTest extends AbstractController
{
    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @test
     * @group LoginController::loginAction
     */
    public function itShouldReturn200AndJwtForDefaultCompany()
    {
        $userOneEmail = 'userOne@companyOne.com';
        $userOnePassword = 'passwordOne';
        $userOneCompanyOneId = 'abc123';
        $userOneCompanyOneRoles = ['Director'];

        $userOne = $this->loadUser($userOneEmail, $userOnePassword, $userOneCompanyOneId, $userOneCompanyOneRoles);
        $this->addCompanyToUser($userOne, 'xyz789', ['Administrator']);

        $client = self::createClient();

        $client->request(
            'POST',
            '/login',
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode([
                'email' => $userOneEmail,
                "password" => $userOnePassword
            ])
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $responseAsArray = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $responseAsArray);

        $jwt = $responseAsArray['token'];
        $decodedJwtClaims = $this->getClaimsFromJwt($jwt);

        $this->assertEquals($userOneCompanyOneId, $decodedJwtClaims->companyId);
        $this->assertEquals($userOneCompanyOneRoles, $decodedJwtClaims->roles);
    }

    /**
     * @test
     * @group LoginController::loginAction
     */
    public function itShouldReturn400WhenEmailAndPasswordAreMissingFromRequestBody()
    {
        $client = self::createClient();

        $client->request(
            'POST',
            '/login',
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode([])
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     * @group LoginController::loginAction
     */
    public function itShouldReturn400WhenPasswordIsEmpty()
    {
        $client = self::createClient();

        $client->request(
            'POST',
            '/login',
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode([
                'email' => 'johnny@somewhere.com',
                'password' => ''])
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     * @group LoginController::loginAction
     */
    public function itShouldReturn400WhenEmailDoesNotMatchAnyUser()
    {
        $client = self::createClient();

        $client->request(
            'POST',
            '/login',
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode([
                'email' => 'johnny@somewhere.com',
                'password' => 'password_for_johnny'])
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     * @group LoginController::loginAction
     */
    public function itShouldReturn401WhenPasswordIsInvalid()
    {
        $userOneEmail = 'userOne@companyOne.com';
        $userOnePassword = 'passwordOne';
        $userOneCompanyOneId = 'abc123';
        $userOneCompanyOneRoles = ['Director'];

        $this->loadUser($userOneEmail, $userOnePassword, $userOneCompanyOneId, $userOneCompanyOneRoles);

        $client = self::createClient();

        $client->request(
            'POST',
            '/login',
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode([
                'email' => $userOneEmail,
                'password' => 'wrong_password_for_this_user'
            ])
        );

        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     * @group LoginController::switchCompanyAction
     */
    public function itShouldReturn200AndJwtWhenSwitchingCompany()
    {
        $userOneEmail = 'userOne@companyOne.com';
        $userOnePassword = 'passwordOne';
        $userOneCompanyOneId = 'abc123';
        $userOneCompanyOneRoles = ['Director'];

        $userOneCompanyTwoId = 'xyz789';
        $userOneCompanyTwoRoles = ['Administrator'];

        $userOne = $this->loadUser($userOneEmail, $userOnePassword, $userOneCompanyOneId, $userOneCompanyOneRoles);
        $this->addCompanyToUser($userOne, $userOneCompanyTwoId, $userOneCompanyTwoRoles);

        $client = self::createClient();
        $jwt = $this->login($client, $userOneEmail, $userOnePassword);

        $client->request(
            'POST',
            '/login/company/' . $userOneCompanyTwoId,
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => "Bearer $jwt")
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $responseAsArray = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $responseAsArray);

        $jwt = $responseAsArray['token'];
        $decodedJwtClaims = $this->getClaimsFromJwt($jwt);

        $this->assertEquals($userOneCompanyTwoId, $decodedJwtClaims->companyId);
        $this->assertEquals($userOneCompanyTwoRoles, $decodedJwtClaims->roles);
    }

    /**
     * @test
     * @group LoginController::switchCompanyAction
     */
    public function itShouldReturn401WhenJwtMissingFromRequestHeaders()
    {
        $client = self::createClient();

        $client->request(
            'POST',
            '/login/company/abc123',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => "Bearer ")
        );

        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     * @group LoginController::switchCompanyAction
     */
    public function itShouldReturn403WhenRequiredRoleToSwitchCompanyIsNotInJwt()
    {
        $userOneEmail = 'userOne@companyOne.com';
        $userOnePassword = 'passwordOne';
        $userOneCompanyOneId = 'abc123';

        $this->loadUser($userOneEmail, $userOnePassword, $userOneCompanyOneId, ['BogusRole']);

        $client = self::createClient();
        $jwt = $this->login($client, $userOneEmail, $userOnePassword);

        $client->request(
            'POST',
            '/login/company/' . $userOneCompanyOneId,
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
     * @group LoginController::refreshJwtAction
     */
    public function itShouldReturn200AndRefreshJwt()
    {
        $userOneEmail = 'userOne@companyOne.com';
        $userOnePassword = 'passwordOne';
        $userOneCompanyOneId = 'abc123';
        $userOneCompanyOneRoles = ['Director'];

        $userOneCompanyTwoId = 'xyz789';
        $userOneCompanyTwoRoles = ['Administrator'];

        $userOne = $this->loadUser($userOneEmail, $userOnePassword, $userOneCompanyOneId, $userOneCompanyOneRoles);
        $this->addCompanyToUser($userOne, $userOneCompanyTwoId, $userOneCompanyTwoRoles);

        $client = self::createClient();
        $jwt = $this->login($client, $userOneEmail, $userOnePassword);

        $client->request(
            'PUT',
            '/login',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => "Bearer $jwt")
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $responseAsArray = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $responseAsArray);

        $jwt = $responseAsArray['token'];
        $decodedJwtClaims = $this->getClaimsFromJwt($jwt);

        $this->assertEquals($userOneCompanyOneId, $decodedJwtClaims->companyId);
        $this->assertEquals($userOneCompanyOneRoles, $decodedJwtClaims->roles);
    }
}
