<?php

namespace TestTranslation\Utils\Silex\EventSubscriber;

use Silex\Application;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionListener implements EventSubscriberInterface
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        switch (true) {
            case method_exists($exception, 'getStatusCode'):
                $code = $exception->getStatusCode();
                break;
            case method_exists($exception, 'getCode'):
                $code = $exception->getCode();
                break;
            default:
                $code = 500;
                break;
        }

        if (is_a($exception, "InvalidArgumentException")) {
            $code = 400;
        }
        $event->setResponse(
            new JsonResponse(
                (true === $this->app["debug"] || 500 !== $code) ? $exception->getMessage() : null,
                $code
            )
        );
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 32],
        ];
    }
}
