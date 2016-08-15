<?php

namespace Bendbennett\DemoBundle\Controller;

use Bendbennett\DemoBundle\Document\UserCompany;
use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Config;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\InvalidArgumentException;

/**
 * @Config\Route("/login")
 */
class LoginController extends Controller
{
    /**
     * @var \Bendbennett\DemoBundle\Service\AuthenticationServiceInterface
     * @DI\Inject("bendbennett_demo.service.authentication_service")
     */
    protected $authenticationService;

    /**
     * @var \Bendbennett\DemoBundle\Service\JwtServiceInterface
     * @DI\Inject("bendbennett_demo.service.jwt_service")
     */
    protected $jwtService;

    /**
     * @var \Bendbennett\DemoBundle\Service\ActiveJwtServiceInterface
     * @DI\Inject("bendbennett_demo.service.active_jwt_service")
     */
    protected $activeJwtService;

    /**
     * @Config\Route("")
     * @Config\Method({"POST"})
     *
     * @SWG\Definition(
     *     definition="Login",
     *     required={"email", "password"},
     *     type="object",
     *     @SWG\Property(property="email", type="string", example="administrator@demo.com"),
     *     @SWG\Property(property="password", type="string", example="admin"),
     *     @SWG\Property(property="companyId", type="string", example="xyz789"),
     * )
     * @SWG\Post(
     *     path="/login",
     *     summary="Login",
     *     description="Login and obtain JWT. The companyId parameter is optional but allows direct login to specific user company.",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     tags={"Login"},
     *     @SWG\Parameter(name="Content-Type", in="header", type="string", default="application/json"),
     *     @SWG\Parameter(name="Body", in="body", type="string", required=true, @SWG\Schema(ref="#/definitions/Login"),),
     *     @SWG\Response(response="200", description="Success")
     * )
     */
    public function login(Request $request) : JsonResponse
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $companyId = $request->request->get('companyId');

        if (empty($email) || empty($password)) {
            throw new InvalidArgumentException(sprintf('Email = %s and/or Password are empty.', $email));
        }

        if (is_string($companyId)) {
            $user = $this->authenticationService->loginToCompany($email, $password, $companyId);
            $selectedUserCompany = $user->getUserCompanyById($companyId);
        } else {
            $user = $this->authenticationService->login($email, $password);
            $selectedUserCompany = $user->getUserCompanies()->first();
        }

        $customClaims['roles'] = $selectedUserCompany->getRoles();
        $customClaims['companyId'] = $selectedUserCompany->getCompanyId();

        $jwt = $this->jwtService->generateJwt($user->getId(), $customClaims);

        return new JsonResponse(['token' => $jwt]);
    }

    /**
     * @Config\Route("")
     * @Config\Method({"PUT"})
     * @Config\Security("has_role('User')")
     * This ensures that any user with a legitimate JWT which contains role(s) that include
     * "User" can access this endpoint (see role_hierarchy in security.yml)
     *
     * @SWG\Put(
     *     path="/login",
     *     summary="Refresh JWT",
     *     description="Supply a valid JWT to obtain a new JWT.",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     tags={"Login"},
     *     @SWG\Parameter(name="Content-Type", in="header", type="string", default="application/json"),
     *     @SWG\Parameter(name="Authorization", in="header", type="string", required=true, default="Bearer {jwt}"),
     *     @SWG\Response(response="200", description="Success")
     * )
     */
    public function refreshJwt() : JsonResponse
    {
        $payload = $this->activeJwtService->getPayload();
        $jwt = $this->jwtService->generateJwt($payload['sub'], ['roles' => $payload['roles'], 'companyId' => $payload['companyId']]);
        /**
         * @todo invalidate / blacklist token supplied in Authorization header
         */

        return new JsonResponse(['token' => $jwt]);
    }

    /**
     * @Config\Route("/company/{companyId}")
     * @Config\Method({"POST"})
     * @Config\Security("has_role('User')")
     * This ensures that any user with a legitimate JWT which contains role(s) that include
     * "User" can access this endpoint (see role_hierarchy in security.yml)
     *
     * @SWG\Definition(
     *     definition="SwitchCompany",
     *     type="object",
     *     @SWG\Property(property="companyId", type="string", example="xyz789"),
     * )
     * @SWG\Post(
     *     path="/login/company/{companyId}",
     *     summary="Switch Company",
     *     description="Supply companyId and valid JWT to generate a new JWT specific to the companyId supplied.",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     tags={"Login"},
     *     @SWG\Parameter(name="Content-Type", in="header", type="string", default="application/json"),
     *     @SWG\Parameter(name="Authorization", in="header", type="string", required=true, default="Bearer {jwt}"),
     *     @SWG\Parameter(name="companyId", in="path", type="string", required=true, default="xyz789"),
     *     @SWG\Parameter(name="Body", in="body", type="string", @SWG\Schema(ref="#/definitions/SwitchCompany"),),
     *     @SWG\Response(response="200", description="Success")
     * )
     */
    public function switchCompany(string $companyId) : JsonResponse
    {
        if (empty($companyId)) {
            throw new InvalidArgumentException('companyId is empty.');
        }

        $user = $this->getUser();
        $requestedUserCompany = $user->getUserCompanyById($companyId);

        if (!$requestedUserCompany instanceof UserCompany) {
            throw new InvalidArgumentException(sprintf('User is not associated with company = %s.', $companyId));
        }

        $customClaims['roles'] = $requestedUserCompany->getRoles();
        $customClaims['companyId'] = $requestedUserCompany->getCompanyId();
        /**
         * @todo invalidate / blacklist token supplied in Authorization header
         */

        $jwt = $this->jwtService->generateJwt($user->getId(), $customClaims);

        return new JsonResponse(['token' => $jwt]);
    }

}
