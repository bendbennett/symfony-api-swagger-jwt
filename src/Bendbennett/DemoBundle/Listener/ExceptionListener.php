<?php

namespace Bendbennett\DemoBundle\Listener;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class ExceptionListener
{
    const EXCEPTION_TO_STATUS_CODE_MAP = [
        AccessDeniedException::class => Response::HTTP_FORBIDDEN,
        AccessDeniedHttpException::class => Response::HTTP_FORBIDDEN,
        AuthenticationException::class => Response::HTTP_UNAUTHORIZED,
        BadCredentialsException::class => Response::HTTP_UNAUTHORIZED,
        DocumentNotFoundException::class => Response::HTTP_NOT_FOUND,
        InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
        NotFoundHttpException::class => Response::HTTP_NOT_FOUND,
        UnauthorizedHttpException::class => Response::HTTP_UNAUTHORIZED
    ];

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        $response = new JsonResponse();

        if (array_key_exists(get_class($exception), self::EXCEPTION_TO_STATUS_CODE_MAP)) {
            $response->setStatusCode(self::EXCEPTION_TO_STATUS_CODE_MAP[get_class($exception)]);
        } else {
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $response->setData(["message" => $exception->getMessage()]);

        $event->setResponse($response);
    }
}
