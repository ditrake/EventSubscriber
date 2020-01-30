<?php
/**
 * 24.01.2020.
 */

declare(strict_types=1);

namespace srr\EventSubscriber;

use App\Exception\DataProviderException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\{JsonResponse, Response};
use Symfony\Component\HttpKernel\{Event\ExceptionEvent, Exception\HttpExceptionInterface, KernelEvents};

/**
 * ExceptionSubscriber.
 * Fired on exceptions, returns Json Response.
 */
class ExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * @var bool
     */
    private bool $debug;

    /**
     * ExceptionSubscriber constructor.
     *
     * @param bool $debug
     */
    public function __construct(bool $debug)
    {
        $this->debug = $debug;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    /**
     * @param ExceptionEvent $event
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $this->supports($event);
        if ($exception === null) {
            return;
        }

        $requestFormat = $event->getRequest()->headers->get('Content-type', 'application/vnd.api+json');
        if ($requestFormat !== 'json' && $requestFormat !== 'application/vnd.api+json') {
            return;
        }

        $response = new JsonResponse();
        $response->headers->set('Content-Type', 'application/problem+json');
        $response->setStatusCode($exception->getStatusCode());

        $data = $this->makeJsonContent($exception);
        if ($this->debug) {
            $data = \array_merge($data, ['trace' => $exception->getTrace()]);
        }
        $response->setData($data);

        $event->setResponse($response);
    }

    /**
     * @param HttpExceptionInterface $exception
     *
     * @return array
     */
    protected function makeJsonContent(HttpExceptionInterface $exception): array
    {
        return [
            'error' => [
                'code' => $exception->getStatusCode(),
                'message' => \method_exists($exception, 'getMessage') ? $exception->getMessage() : 'Bad request',
            ],
        ];
    }

    /**
     * @param ExceptionEvent $event
     *
     * @return HttpExceptionInterface|null
     */
    protected function supports(ExceptionEvent $event): ?HttpExceptionInterface
    {
        $ex = $event->getThrowable();
        if (!$ex instanceof HttpExceptionInterface) {
            return null;
        }

        $support = \in_array($ex->getStatusCode(), [
            Response::HTTP_BAD_REQUEST,
            Response::HTTP_FORBIDDEN,
            Response::HTTP_NOT_FOUND,
            Response::HTTP_METHOD_NOT_ALLOWED,
            Response::HTTP_NOT_ACCEPTABLE,
            Response::HTTP_REQUEST_TIMEOUT,
            Response::HTTP_CONFLICT,
            Response::HTTP_GONE,
        ], true);

        if ($support) {
            return $ex;
        }

        return null;
    }
}
