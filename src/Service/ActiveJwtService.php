<?php

namespace App\Service;

use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ActiveJwtService implements ActiveJwtServiceInterface
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var JWTEncoderInterface
     */
    protected $jwtEncoder;

    /**
     * @var TokenExtractorInterface
     */
    protected $authorizationHeaderTokenExtractor;

    public function __construct(RequestStack $requestStack, JWTEncoderInterface $jwtEncoder, TokenExtractorInterface $authorizationHeaderTokenExtractor)
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->jwtEncoder = $jwtEncoder;
        $this->authorizationHeaderTokenExtractor = $authorizationHeaderTokenExtractor;
    }

    public function getPayload(): array
    {
        if (!$this->request instanceof Request) {
            return [];
        }

        if (!$this->request->headers->has('Authorization')) {
            return [];
        }

        $token = $this->authorizationHeaderTokenExtractor->extract($this->request);

        if (!$token) {
            return [];
        }

        $payload = $this->jwtEncoder->decode($token);

        if (!$payload) {
            return [];
        }

        return $payload;
    }

    public function getPayloadRoles(): array
    {
        $payload = $this->getPayload();
        return $payload['roles'] ?? [];
    }

    public function getPayloadId(): string
    {
        $payload = $this->getPayload();
        return $payload['sub'] ?? '';
    }
}
