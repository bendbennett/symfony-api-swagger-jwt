<?php

namespace App\Security;

use App\Document\User;
use App\Document\UserCompany;
use App\Service\ActiveJwtServiceInterface;
use App\Service\SerializerServiceInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    const VIEW = 'view';
    const CREATE = 'create';
    const GLOBAL_ROLES = ['ROLE_ADMIN'];
    const ALLOWED_ROLES = ['ROLE_DIRECTOR'];

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ActiveJwtServiceInterface
     */
    private $activeJwtService;

    /**
     * @var SerializerServiceInterface
     */
    private $serializerService;

    public function __construct(RequestStack $requestStack, ActiveJwtServiceInterface $activeJwtService, SerializerServiceInterface $serializerService)
    {
        $this->requestStack = $requestStack;
        $this->activeJwtService = $activeJwtService;
        $this->serializerService = $serializerService;
    }

    /**
     * Can't use $subject as current logged in user is passed as $subject arg by default
     * rather than the user generated from the paramConverter on the controller.
     * However, paramConverter sets user on the request which can be obtained from the request.
     *
     */
    protected function supports($attribute, $subject) : bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, array(self::VIEW, self::CREATE))) {
            return false;
        }

        $request = $this->requestStack->getCurrentRequest();
        $user = $request->get('user');

        if (!$user instanceof User) {
            $user = $this->serializerService->deserializeUserFromJson($request->getContent(), 'json');
        }

        // only vote on User objects inside this voter
        if (!$user instanceof User) {
            return false;
        }

        return true;
    }

    /**
     * Can't use $subject as current logged in user is passed as $subject arg by default
     * rather than the user generated from the paramConverter on the controller.
     * However, paramConverter sets user on the request which can be obtained from the requestStack (see below).
     *
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token) : bool
    {
        $request = $this->requestStack->getCurrentRequest();
        $user = $request->get('user');

        if (!$user instanceof User) {
            $user = $this->serializerService->deserializeUserFromJson($request->getContent(), 'json');
        }

        if (!$user instanceof User) {
            return false;
        }

        $loggedInUser = $token->getUser();

        if (!$loggedInUser instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($user, $loggedInUser);
            case self::CREATE:
                return $this->canCreate($user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canView(User $userDeserializedFromRequest, User $loggedInUser) : bool
    {
        if (count(array_intersect($this->activeJwtService->getPayloadRoles(), self::GLOBAL_ROLES)) > 0) {
            return true;
        }

        if (count(array_intersect($this->activeJwtService->getPayloadRoles(), self::ALLOWED_ROLES)) > 0) {
            if ($userDeserializedFromRequest->getUserCompanyById($this->activeJwtService->getPayload()['companyId']) instanceof UserCompany) {
                return true;
            }
        }

        if ($userDeserializedFromRequest->getId() === $loggedInUser->getId()) {
            return true;
        }

        return false;
    }

    private function canCreate(User $userDeserializedFromRequest) : bool
    {
        if (count(array_intersect($this->activeJwtService->getPayloadRoles(), self::GLOBAL_ROLES)) > 0) {
            return true;
        }

        if (count(array_intersect($this->activeJwtService->getPayloadRoles(), self::ALLOWED_ROLES)) > 0) {
            foreach ($userDeserializedFromRequest->getUserCompanies() as $userCompany) {
                if ($userCompany->getCompanyId() === $this->activeJwtService->getPayload()['companyId'])
                {
                    return true;
                }
            }
        }

        return false;
    }
}
