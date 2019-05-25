<?php

namespace Bendbennett\DemoBundle\Security;

use Bendbennett\DemoBundle\Document\User;
use Bendbennett\DemoBundle\Manager\UserManager;
use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Guard\AuthenticatorInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;

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

    public function start(Request $request, AuthenticationException $authException = null)
    {
        throw $authException;
    }

    public function getCredentials(Request $request) : string
    {
        if (!$request->headers->has('Authorization')) {
            throw new AuthenticationException('Authorization header is missing from request.');
        }

        $token = $this->authorizationHeaderTokenExtractor->extract($request);

        if (!$token) {
            throw new AuthenticationException('Token could not be extracted from Authorization header.');
        }

        return $token;
    }

    public function supports(\Symfony\Component\HttpFoundation\Request $request)
    {
        if (!$request->headers->has('Authorization')) {
            throw new AuthenticationException('Authorization header is missing from request.');
        }

        $token = $this->authorizationHeaderTokenExtractor->extract($request);

        if (!$token) {
            throw new AuthenticationException('Token could not be extracted from Authorization header.');
        }

        return $token;
    }

    public function createAuthenticatedToken(\Symfony\Component\Security\Core\User\UserInterface $user, $providerKey)
    {
        return new PostAuthenticationGuardToken($user, $providerKey, $user->getRoles());
    }

    public function getUser($token, UserProviderInterface $userProvider) : User
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

    public function checkCredentials($token, UserInterface $user)
    {
        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        throw $exception;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return;
    }

    public function supportsRememberMe()
    {
        return false;
    }
}
