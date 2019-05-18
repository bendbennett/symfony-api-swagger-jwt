<?php

namespace Bendbennett\DemoBundle\Controller;

use Bendbennett\DemoBundle\Document\User;
use JMS\DiExtraBundle\Annotation as DI;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Config;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * @Config\Route("/users")
 * @OA\Info(title="Demo API", version="0.1")
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
     * @OA\Get(
     *     path="/users",
     *     summary="Get Users",
     *     tags={"Users"},
     *     security={{"jwt":{}}},
     *     @OA\Response(response="200", description="Success", @OA\MediaType(mediaType="application/json"))
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
     * @OA\Get(
     *     path="/users/{userId}",
     *     summary="Get User by Id",
     *     tags={"Users"},
     *     security={{"jwt":{}}},
     *     @OA\Parameter(name="userId", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response="200", description="Success.", @OA\MediaType(mediaType="application/json"))
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
     * @OA\Get(
     *     path="/users/{key}/{value}",
     *     summary="Get User by Key-Value",
     *     tags={"Users"},
     *     security={{"jwt":{}}},
     *     @OA\Parameter(name="key", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="value", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response="200", description="All Users.", @OA\MediaType(mediaType="application/json"))
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
     * @OA\Post(
     *     path="/users",
     *     summary="Create User",
     *     description="Create user by deserializing json submitted in request body.",
     *     tags={"Users"},
     *     security={{"jwt":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/User")),
     *     @OA\Response(response="200", description="Success", @OA\MediaType(mediaType="application/json"))
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
     * @OA\Patch(
     *     path="/users/{userId}",
     *     summary="Update User",
     *     description="Update user by deserializing json submitted in request body.",
     *     tags={"Users"},
     *     security={{"jwt":{}}},
     *     @OA\Parameter(name="userId", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/User")),
     *     @OA\Response(response="200", description="Success", @OA\MediaType(mediaType="application/json"))
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
     * @OA\Delete(
     *     path="/users/{userId}",
     *     summary="Delete User",
     *     description="Delete user by Id.",
     *     tags={"Users"},
     *     security={{"jwt":{}}},
     *     @OA\Parameter(name="userId", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response="200", description="Success", @OA\MediaType(mediaType="application/json"))
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
