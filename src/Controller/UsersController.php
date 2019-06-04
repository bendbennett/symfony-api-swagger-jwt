<?php

namespace App\Controller;

use App\Document\User;
use App\Manager\UserManager;
use App\Service\SerializerServiceInterface;
use App\Service\ValidatorServiceInterface;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Config;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/users")
 * @OA\Info(title="Demo API", version="0.1")
 */
class UsersController
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
        ValidatorServiceInterface $validatorService)
    {
        $this->userManager = $userManager;
        $this->serializerService = $serializerService;
        $this->validatorService = $validatorService;
    }

    /**
     * @Route("", methods={"GET"})
     * @Config\Security("is_granted('ROLE_ADMIN')")
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

        return new JsonResponse($serializedUsers, 200, [], true);
    }

    /**
     * @Route("/{id}", methods={"GET"})
     * @Config\Security("is_granted('view', user)")
     * @Config\ParamConverter("user", class="App\Document\User")
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

        return new JsonResponse($serializedUser, 200, [], true);
    }

    /**
     * @Route("/{key}/{value}", methods={"GET"})
     * @Config\Security("is_granted('ROLE_ADMIN')")
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

        return new JsonResponse($serializedUser, 200, [], true);
    }

    /**
     * @Route("", methods={"POST"})
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

        return new JsonResponse($serializedUser, 200, [], true);
    }

    /**
     * @Route("/{id}", methods={"PATCH"})
     * @Config\Security("is_granted('ROLE_DIRECTOR')")
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

        return new JsonResponse($serializedUser, 200, [], true);
    }

    /**
     * @Route("/{id}", methods={"DELETE"})
     * @Config\Security("is_granted('ROLE_ADMIN')")
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

        return new JsonResponse(null, 204);
    }
}
