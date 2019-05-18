<?php

namespace Bendbennett\DemoBundle\Controller;

use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Config;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Config\Route("/users/{id}/companies/{companyId}")
 */
class UsersCompaniesController extends Controller
{
    /**
     * @var \Bendbennett\DemoBundle\Manager\UserManager
     * @DI\Inject("Bendbennett\DemoBundle\Manager\UserManager")
     */
    protected $userManager;

    /**
     * @var \Bendbennett\DemoBundle\Service\SerializerServiceInterface
     * @DI\Inject("Bendbennett\DemoBundle\Service\SerializerService")
     */
    protected $serializerService;

    /**
     * @var \Bendbennett\DemoBundle\Service\ValidatorServiceInterface
     * @DI\Inject("Bendbennett\DemoBundle\Service\ValidatorService")
     */
    protected $validatorService;

    /**
     * @Config\Route("")
     * @Config\Method({"PATCH"})
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
