<?php
namespace Bendbennett\DemoBundle\Service;

interface ValidatorServiceInterface
{
    public function isValid($documentToValidate) : bool;

    public function getValidationErrors($documentToValidate) : string;
}