<?php

namespace Bendbennett\DemoBundle\Controller;

use Bendbennett\DemoBundle\Document\User;
use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Config;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * @Config\Route("/users")
 * @SWG\Info(title="Demo API", version="0.1")
 */
class UsersController extends Controller
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
     * @Config\Method({"GET"})
     * @Config\Security("has_role('Administrator')")
     *
     * @SWG\Get(
     *     path="/users",
     *     summary="Get Users",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     tags={"Users"},
     *     @SWG\Parameter(name="Content-Type", in="header", type="string", default="application/json"),
     *     @SWG\Parameter(name="Authorization", in="header", type="string", required=true, default="Bearer {jwt}"),
     *     @SWG\Response(response="200", description="Success")
     * )
     */
    public function indexAction()
    {
        $users = $this->userManager->getAllUsers();
        $serializedUsers = $this->serializerService->serializeUsers($users, 'json');

        return $this->preparedResponse($serializedUsers);
    }

    /**
     * @Config\Route("/{id}")
     * @Config\Method({"GET"})
     * @Config\Security("is_granted('view', user)")
     * @Config\ParamConverter("user", class="Bendbennett\DemoBundle\Document\User")
     *
     * @SWG\Get(
     *     path="/users/{userId}",
     *     summary="Get User by Id",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     tags={"Users"},
     *     @SWG\Parameter(name="Content-Type", in="header", type="string", default="application/json"),
     *     @SWG\Parameter(name="Authorization", in="header", type="string", required=true, default="Bearer {jwt}"),
     *     @SWG\Parameter(name="userId", in="path", type="string", required=true),
     *     @SWG\Response(response="200", description="Success.")
     * )
     */
    public function showAction(User $user)
    {
        $serializedUser = $this->serializerService->serializeUser($user, 'json');

        return $this->preparedResponse($serializedUser);
    }

    /**
     * @Config\Route("/{key}/{value}")
     * @Config\Method({"GET"})
     * @Config\Security("has_role('Administrator')")
     *
     * @SWG\Get(
     *     path="/users/{key}/{value}",
     *     summary="Get User by Key-Value",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     tags={"Users"},
     *     @SWG\Parameter(name="Content-Type", in="header", type="string", default="application/json"),
     *     @SWG\Parameter(name="Authorization", in="header", type="string", required=true, default="Bearer {jwt}"),
     *     @SWG\Parameter(name="key", in="path", type="string", required=true),
     *     @SWG\Parameter(name="value", in="path", type="string", required=true),
     *     @SWG\Response(response="200", description="All Users.")
     * )
     */
    public function searchAction(string $key, string $value)
    {
        $user = $this->userManager->getUserByKeyValue([$key => $value]);
        $serializedUser = $this->serializerService->serializeUser($user, 'json');

        return $this->preparedResponse($serializedUser);
    }

    /**
     * @Config\Route("")
     * @Config\Method({"POST"})
     * @Config\Security("is_granted('create', user)")
     *
     * @SWG\Post(
     *     path="/users",
     *     summary="Create User",
     *     description="Create user by deserializing json submitted in request body.",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     tags={"Users"},
     *     @SWG\Parameter(name="Content-Type", in="header", type="string", default="application/json"),
     *     @SWG\Parameter(name="Authorization", in="header", type="string", required=true, default="Bearer {jwt}"),
     *     @SWG\Parameter(name="Body", in="body", type="string", required=true, @SWG\Schema(ref="#/definitions/User"),),
     *     @SWG\Response(response="200", description="Success")
     * )
     */
    public function storeAction(Request $request)
    {
        $user = $this->serializerService->deserializeUserFromJson($request->getContent(), 'json');

        if (is_string($request->request->get('password'))) {
            $user = $this->userManager->encodePassword($user, $request->request->get('password'));
        }

        if (!$this->validatorService->isValid($user)) {
            return $this->preparedResponse($this->validatorService->getValidationErrors($user));
        }

        $this->userManager->storeUser($user);
        $serializedUser = $this->serializerService->serializeUser($user, 'json');

        return $this->preparedResponse($serializedUser);
    }

    /**
     * @Config\Route("/{id}")
     * @Config\Method({"PATCH"})
     *
     * @SWG\Patch(
     *     path="/users/{userId}",
     *     summary="Update User",
     *     description="Update user by deserializing json submitted in request body.",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     tags={"Users"},
     *     @SWG\Parameter(name="Content-Type", in="header", type="string", default="application/json"),
     *     @SWG\Parameter(name="Authorization", in="header", type="string", required=true, default="Bearer {jwt}"),
     *     @SWG\Parameter(name="userId", in="path", type="string", required=true),
     *     @SWG\Parameter(name="Body", in="body", type="string", required=true, @SWG\Schema(ref="#/definitions/User"),),
     *     @SWG\Response(response="200", description="Success")
     * )
     */
    public function editAction(Request $request, string $id)
    {
        $user = $this->serializerService->deserializeUserFromJson($request->getContent(), 'json', $id);

        if (is_string($request->request->get('password'))) {
            $user = $this->userManager->encodePassword($user, $request->request->get('password'));
        }

        if (!$this->validatorService->isValid($user)) {
            throw new ValidatorException($this->validatorService->getValidationErrors($user));
        }

        $this->userManager->storeUser($user);
        $serializedUser = $this->serializerService->serializeUser($user, 'json');

        return $this->preparedResponse($serializedUser);
    }

    /**
     * @Config\Route("/{id}")
     * @Config\Method({"DELETE"})
     *
     * @SWG\Delete(
     *     path="/users/{userId}",
     *     summary="Delete User",
     *     description="Delete user by Id.",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     tags={"Users"},
     *     @SWG\Parameter(name="Content-Type", in="header", type="string", default="application/json"),
     *     @SWG\Parameter(name="Authorization", in="header", type="string", required=true, default="Bearer {jwt}"),
     *     @SWG\Parameter(name="userId", in="path", type="string", required=true),
     *     @SWG\Response(response="200", description="Success")
     * )
     */
    public function deleteAction(string $id)
    {
        $user = $this->userManager->getUser($id);
        $this->userManager->removeUser($user);

        return $this->preparedResponse();
    }

    /**
     * Convenience to add Content-Type header and body to response
     *
     * @param string|null $responseBody
     * @return Response
     */
    private function preparedResponse(string $responseBody = null)
    {
        $response = new Response($responseBody);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
