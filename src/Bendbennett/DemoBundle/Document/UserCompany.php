<?php

namespace Bendbennett\DemoBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @MongoDB\EmbeddedDocument
 * @SWG\Definition(definition="UserCompany")
 */
class UserCompany
{
    /**
     * @MongoDB\Field(type="string")
     * @JMS\Type("string")
     * @JMS\SerializedName("companyId")
     * @SWG\Property(type="string")
     */
    protected $companyId;

    /**
     * @MongoDB\Field(type="collection")
     * @JMS\Type("array")
     * @var string[]
     * @Assert\All({@Assert\NotBlank, @Assert\Type("string")})
     * @SWG\Property()
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
