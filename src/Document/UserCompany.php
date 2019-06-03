<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use JMS\Serializer\Annotation as JMS;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @MongoDB\EmbeddedDocument
 * @OA\Schema(schema="UserCompany")
 */
class UserCompany
{
    /**
     * @MongoDB\Id
     * @JMS\Type("string")
     */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     * @JMS\Type("string")
     * @JMS\SerializedName("companyId")
     * @OA\Property(type="string")
     */
    protected $companyId;

    /**
     * @MongoDB\Field(type="collection")
     * @JMS\Type("array")
     * @var string[]
     * @Assert\All({@Assert\NotBlank, @Assert\Type("string")})
     * @OA\Property()
     */
    protected $roles;

    /**
     *
     * @param string $companyId
     * @return self
     */
    public function setCompanyId($companyId)
    {
        $this->companyId = $companyId;
        return $this;
    }

    /**
     *
     * @return string $companyId
     */
    public function getCompanyId()
    {
        return $this->companyId;
    }

    /**
     * Set roles
     *
     * @param array $roles
     * @return self
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * Get roles
     *
     * @return string $roles
     */
    public function getRoles()
    {
        return $this->roles;
    }
}
