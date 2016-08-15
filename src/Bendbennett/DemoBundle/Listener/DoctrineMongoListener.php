<?php

namespace Bendbennett\DemoBundle\Listener;

use Bendbennett\DemoBundle\Document\User;
use Bendbennett\DemoBundle\Service\ActiveJwtServiceInterface;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;

class DoctrineMongoListener
{
    /**
     * @var ActiveJwtServiceInterface
     */
    protected $activeJwtService;

    public function __construct(ActiveJwtServiceInterface $activeJwtService)
    {
        $this->activeJwtService = $activeJwtService;
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        $document = $args->getDocument();

        if ($document instanceof User &&
            $document->getId() == $this->activeJwtService->getPayloadId()
        ) {
            $document->setRoles($this->activeJwtService->getPayloadRoles());
        }
    }

}