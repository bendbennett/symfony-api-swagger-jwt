<?php

namespace Bendbennett\DemoBundle\Service;

use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class JwtService implements JwtServiceInterface
{
    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @var \Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface
     */
    protected $jwtEncoder;

    /**
     * @var int
     */
    protected $tokenTimeToLive;

    /**
     * @var array
     */
    protected $registeredClaims;

    /**
     * @var array
     */
    protected $customClaims;

    public function __construct(RequestStack $requestStack, JWTEncoderInterface $jwtEncoder, int $tokenTimeToLive, array $registeredClaims, array $customClaims)
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->jwtEncoder = $jwtEncoder;
        $this->tokenTimeToLive = $tokenTimeToLive;
        $this->registeredClaims = $registeredClaims;
        $this->customClaims = $customClaims;
    }

    public function generateJwt(string $userId, array $customClaims) : string
    {
        $registeredClaims = $this->generateRegisteredClaims($userId);
        $customClaims = $this->generateCustomClaims($customClaims);
        $mergedClaims = array_merge($registeredClaims, $customClaims);

        $token = $this->jwtEncoder->encode($mergedClaims);

        return $token;
    }

    private function generateRegisteredClaims(string $userId) : array
    {
        $registeredClaims = [];

        if (in_array('iss', $this->registeredClaims)) {
            $registeredClaims['iss'] = $this->request->getSchemeAndHttpHost() . $this->request->getRequestUri();
        }

        if (in_array('sub', $this->registeredClaims)) {
            $registeredClaims['sub'] = $userId;
        }

        if (in_array('exp', $this->registeredClaims)) {
            $now = new \DateTime('now');
            $registeredClaims['exp'] = (int)$now->format('U') + (int)$this->tokenTimeToLive;
        }

        if (in_array('iat', $this->registeredClaims)) {
            $now = new \DateTime('now');
            $registeredClaims['iat'] = (int)$now->format('U');
        }

        return $registeredClaims;
    }

    private function generateCustomClaims(array $customClaims)
    {
        $processedCustomClaims = [];

        foreach($customClaims as $claimKey => $claimValue) {
            if (in_array($claimKey, $this->customClaims)) {
                $processedCustomClaims[$claimKey] = $claimValue;
            }
        }

        return $processedCustomClaims;
    }
}
