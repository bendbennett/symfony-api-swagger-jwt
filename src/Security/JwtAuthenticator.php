<?php

namespace App\Security;

use App\Document\User;
use App\Manager\UserManager;
use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AuthenticatorInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use Symfony\Component\Security\Guard\Token\GuardTokenInterface;

class JwtAuthenticator implements AuthenticatorInterface
{
    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var JWTEncoderInterface
     */
    private $jwtEncoder;

    /**
     * @var TokenExtractorInterface
     */
    private $authorizationHeaderTokenExtractor;

    public function __construct(UserManager $userManager, JWTEncoderInterface $jwtEncoder, TokenExtractorInterface $authorizationHeaderTokenExtractor)
    {
        $this->userManager = $userManager;
        $this->jwtEncoder = $jwtEncoder;
        $this->authorizationHeaderTokenExtractor = $authorizationHeaderTokenExtractor;
    }

    public function start(Request $request, AuthenticationException $authException = null): void
    {
        throw $authException;
    }

    public function supports(Request $request): bool
    {
        if (!$request->headers->has('Authorization')) {
            throw new AuthenticationException('Authorization header is missing from request.');
        }

        return true;
    }

    public function getCredentials(Request $request): string
    {
        $token = $this->authorizationHeaderTokenExtractor->extract($request);

        if (!is_string($token) || empty($token)) {
            throw new AuthenticationException('Token is missing from Authorization header.');
        }

        return $token;
    }

    public function createAuthenticatedToken(UserInterface $user, $providerKey): GuardTokenInterface
    {
        return new PostAuthenticationGuardToken($user, $providerKey, $user->getRoles());
    }

    public function getUser($token, UserProviderInterface $userProvider): User
    {
        $data = $this->jwtEncoder->decode($token);

        if (!$data) {
            throw new AuthenticationException('Token cannot be parsed, is invalid or has expired.');
        }

        if (!is_string($id = $data['sub'])) {
            throw new AuthenticationException(sprintf('Id (%s) extracted from JWT is not a string.', $id));
        }

        try {
            $user = $this->userManager->getUser($id);
        } catch (DocumentNotFoundException $exception) {
            throw new AuthenticationException(sprintf('User with id = %s could not be found.', $id));
        }

        return $user;
    }

    public function checkCredentials($token, UserInterface $user): bool
    {
        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): void
    {
        throw $exception;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): void
    {
        return;
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }
}
