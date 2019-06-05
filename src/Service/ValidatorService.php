<?php

namespace App\Service;

use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidatorService implements ValidatorServiceInterface
{
    /**
     * @var \Symfony\Component\Validator\Validator\ValidatorInterface
     */
    protected $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function isValid($documentToValidate) : bool
    {
        return count($this->validator->validate($documentToValidate)) > 0 ? false : true;
    }

    public function getValidationErrors($documentToValidate) : string
    {
        return (string)$this->validator->validate($documentToValidate);
    }
}
