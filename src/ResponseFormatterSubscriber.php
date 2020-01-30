<?php
/**
 * 24.01.2020.
 */

declare(strict_types=1);

namespace srr\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\HttpKernel\{Event\ViewEvent, KernelEvents};
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Make JSON response for Address or AddressCollection controller result.
 */
class ResponseFormatterSubscriber implements EventSubscriberInterface
{
    private SerializerInterface $serializer;
    private UrlGeneratorInterface $urlGenerator;

    /**
     * ResponseFormatterSubscriber constructor.
     *
     * @param SerializerInterface   $serializer
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(SerializerInterface $serializer, UrlGeneratorInterface $urlGenerator)
    {
        $this->serializer = $serializer;
        $this->urlGenerator = $urlGenerator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['formatResponse', 0],
        ];
    }

    /**
     * Make JsonResponse if controller returns Address or AddressCollection.
     *
     * @param ViewEvent $event
     */
    public function formatResponse(ViewEvent $event): void
    {
        $controllerResult = $event->getControllerResult();

        if ($controllerResult instanceof Response) {
            $this->makeAddressResponse($event);
        }
    }

    /**
     * @param ViewEvent $event
     */
    protected function makeAddressResponse(ViewEvent $event): void
    {
        $response = new JsonResponse();
        $data = [
            'data' => $event->getControllerResult(),
            'links' => $this->getLinks($event->getRequest()),
        ];
        $response->setContent($this->serializer->serialize($data, 'json'))
            ->setStatusCode(Response::HTTP_OK)
            ->headers->add($this->getHeaders())
        ;

        $event->setResponse($response);
    }

    /**
     * @return array
     */
    private function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/vnd.api+json',
        ];
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    private function getLinks(Request $request): array
    {
        $self = $request->attributes->get('_route') !== null
            ? $this->urlGenerator->generate($request->attributes->get('_route'), $request->query->all(), UrlGeneratorInterface::ABSOLUTE_URL)
            : null;

        return [
            'self' => $self,
        ];
    }
}
