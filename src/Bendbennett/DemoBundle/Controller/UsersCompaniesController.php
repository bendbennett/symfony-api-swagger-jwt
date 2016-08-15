<?php

namespace Bendbennett\DemoBundle\Controller;

use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Config;
use Swagger\Annotations as SWG;
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
     * @DI\Inject("bendbennett_demo.manager.user_manager")
     */
    protected $userManager;

    /**
     * @var \Bendbennett\DemoBundle\Service\SerializerServiceInterface
     * @DI\Inject("bendbennett_demo.service.serializer_service")
     */
    protected $serializerService;

    /**
     * @var \Bendbennett\DemoBundle\Service\ValidatorServiceInterface
     * @DI\Inject("bendbennett_demo.service.validator_service")
     */
    protected $validatorService;

    /**
     * @Config\Route("")
     * @Config\Method({"PATCH"})
     *
     * @SWG\Patch(
     *     path="/users/{userId}/companies/{companyId}",
     *     summary="Update User Company",
     *     description="Update user company.",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     tags={"Users - Companies"},
     *     @SWG\Parameter(name="Content-Type", in="header", type="string", default="application/json"),
     *     @SWG\Parameter(name="Authorization", in="header", type="string", required=true, default="Bearer {jwt}"),
     *     @SWG\Parameter(name="userId", in="path", type="string", required=true),
     *     @SWG\Parameter(name="companyId", in="path", type="string", required=true),
     *     @SWG\Parameter(name="Body", in="body", type="string", required=true, @SWG\Schema(ref="#/definitions/UserCompany"),),
     *     @SWG\Response(response="200", description="Success")
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
