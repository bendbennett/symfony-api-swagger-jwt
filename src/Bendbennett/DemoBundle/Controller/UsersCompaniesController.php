<?php

namespace Bendbennett\DemoBundle\Controller;

use Bendbennett\DemoBundle\Manager\UserManager;
use Bendbennett\DemoBundle\Service\SerializerServiceInterface;
use Bendbennett\DemoBundle\Service\ValidatorServiceInterface;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/users/{id}/companies/{companyId}")
 */
class UsersCompaniesController
{
    /**
     * @var \Bendbennett\DemoBundle\Manager\UserManager
     */
    protected $userManager;

    /**
     * @var \Bendbennett\DemoBundle\Service\SerializerServiceInterface
     */
    protected $serializerService;

    /**
     * @var \Bendbennett\DemoBundle\Service\ValidatorServiceInterface
     */
    protected $validatorService;

    public function __construct(
        UserManager $userManager,
        SerializerServiceInterface $serializerService,
        ValidatorServiceInterface $validatorService
    )
    {
        $this->userManager = $userManager;
        $this->serializerService = $serializerService;
        $this->validatorService = $validatorService;
    }

    /**
     * @Route("", methods={"PATCH"})
     *
     * @OA\Patch(
     *     path="/users/{userId}/companies/{companyId}",
     *     summary="Update User Company",
     *     description="Update user company.",
     *     tags={"Users - Companies"},
     *     security={{"jwt":{}}},
     *     @OA\Parameter(name="userId", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="companyId", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UserCompany")),
     *     @OA\Response(response="200", description="Success", @OA\MediaType(mediaType="application/json"))
     * )
     */
    public function editAction(Request $request, string $id, string $companyId)
    {
        $userCompany = $this->serializerService->deserializeUserCompanyFromJson($request->getContent(), 'json', $companyId);

        if (!$this->validatorService->isValid($userCompany)) {
            throw new ValidatorException($this->validatorService->getValidationErrors($userCompany));
        }

        $user = $this->userManager->updateUserCompany($id, $companyId, $userCompany);
        $serializedUser = $this->serializerService->serializeUser($user, 'json');

        $response = new Response($serializedUser, 200, ['Content-Type' => 'application/json']);

        return $response;
    }
}
