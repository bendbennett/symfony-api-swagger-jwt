<?php
namespace App\Service;

interface ValidatorServiceInterface
{
    public function isValid($documentToValidate) : bool;

    public function getValidationErrors($documentToValidate) : string;
}