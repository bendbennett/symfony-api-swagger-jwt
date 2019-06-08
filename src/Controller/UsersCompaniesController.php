<?php

namespace App\Controller;

use App\Manager\UserManager;
use App\Service\SerializerServiceInterface;
use App\Service\ValidatorServiceInterface;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Config;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * @Route("/users/{id}/companies/{companyId}")
 */
class UsersCompaniesController
{
    /**
     * @var \App\Manager\UserManager
     */
    protected $userManager;

    /**
     * @var \App\Service\SerializerServiceInterface
     */
    protected $serializerService;

    /**
     * @var \App\Service\ValidatorServiceInterface
     */
    protected $validatorService;

    public function __construct(
        UserManager $userManager,
        SerializerServiceInterface $serializerService,
        ValidatorServiceInterface $validatorService
    ) {
        $this->userManager = $userManager;
        $this->serializerService = $serializerService;
        $this->validatorService = $validatorService;
    }

    /**
     * @Route("", methods={"PATCH"})
     * @Config\Security("is_granted('ROLE_ADMIN')")
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
    public function editAction(Request $request, string $id, string $companyId): JsonResponse
    {
        $userCompany = $this->serializerService->deserializeUserCompanyFromJson($request->getContent(), 'json', $companyId);

        if (!$this->validatorService->isValid($userCompany)) {
            throw new ValidatorException($this->validatorService->getValidationErrors($userCompany));
        }

        $user = $this->userManager->updateUserCompany($id, $companyId, $userCompany);
        $serializedUser = $this->serializerService->serializeUser($user, 'json');

        return new JsonResponse($serializedUser, 200, [], true);
    }
}
