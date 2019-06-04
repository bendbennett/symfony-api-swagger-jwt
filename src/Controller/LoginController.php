<?php

namespace App\Controller;

use App\Document\UserCompany;
use App\Service\ActiveJwtServiceInterface;
use App\Service\AuthenticationServiceInterface;
use App\Service\JwtServiceInterface;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Config;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\InvalidArgumentException;
use Symfony\Component\Security\Core\Security;

/**
 * @Route("/login")
 */
class LoginController
{
    /**
     * @var \App\Service\AuthenticationServiceInterface
     */
    protected $authenticationService;

    /**
     * @var \App\Service\JwtServiceInterface
     */
    protected $jwtService;

    /**
     * @var \App\Service\ActiveJwtServiceInterface
     */
    protected $activeJwtService;

    /**
     * @var \Symfony\Component\Security\Core\Security
     */
    protected $security;

    public function __construct(
        AuthenticationServiceInterface $authenticationService,
        JwtServiceInterface $jwtService,
        ActiveJwtServiceInterface $activeJwtService,
        Security $security)
    {
        $this->authenticationService = $authenticationService;
        $this->jwtService = $jwtService;
        $this->activeJwtService = $activeJwtService;
        $this->security = $security;
    }

    /**
     * @Route("", methods={"POST"})
     *
     * @OA\Schema(
     *     schema="Login",
     *     required={"email", "password"},
     *     type="object",
     *     @OA\Property(property="email", type="string", example="administrator@demo.com"),
     *     @OA\Property(property="password", type="string", example="admin"),
     *     @OA\Property(property="companyId", type="string", example="xyz789"),
     * )
     * @OA\Post(
     *     path="/login",
     *     summary="Login",
     *     description="Login and obtain JWT. The companyId parameter is optional but allows direct login to specific user company.",
     *     tags={"Login"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Login")),
     *     @OA\Response(response="200", description="Success", @OA\MediaType(mediaType="application/json"))
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
     * @Route("", methods={"PUT"})
     * @Config\Security("is_granted('ROLE_USER')")
     * This ensures that any user with a legitimate JWT which contains role(s) that include
     * "ROLE_USER" can access this endpoint (see role_hierarchy in security.yml)
     *
     * @OA\SecurityScheme(
     *     type="http",
     *     scheme="bearer",
     *     securityScheme="jwt"
     * )
     *
     * @OA\Put(
     *     path="/login",
     *     summary="Refresh JWT",
     *     description="Supply a valid JWT to obtain a new JWT.",
     *     tags={"Login"},
     *     security={{"jwt":{}}},
     *     @OA\Response(response="200", description="Success", @OA\MediaType(mediaType="application/json"))
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
     * @Route("/company/{companyId}", methods={"POST"})
     * @Config\Security("is_granted('ROLE_USER')")
     * This ensures that any user with a legitimate JWT which contains role(s) that include
     * "ROLE_USER" can access this endpoint (see role_hierarchy in security.yml)
     *
     * @OA\Schema(
     *     schema="SwitchCompany",
     *     type="object",
     *     @OA\Property(property="companyId", type="string", example="xyz789"),
     * )
     * @OA\Post(
     *     path="/login/company/{companyId}",
     *     summary="Switch Company",
     *     description="Supply companyId and valid JWT to generate a new JWT specific to the companyId supplied.",
     *     tags={"Login"},
     *     security={{"jwt":{}}},
     *     @OA\Parameter(name="companyId", in="path", required=true, @OA\Schema(type="string", default="xyz789")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/SwitchCompany")),
     *     @OA\Response(response="200", description="Success", @OA\MediaType(mediaType="application/json"))
     * )
     */
    public function switchCompany(string $companyId) : JsonResponse
    {
        if (empty($companyId)) {
            throw new InvalidArgumentException('companyId is empty.');
        }

        $user = $this->security->getUser();
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
