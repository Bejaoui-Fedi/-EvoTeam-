<?php

namespace App\EventSubscriber;

use App\Service\RecaptchaService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class RecaptchaSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RecaptchaService $recaptchaService,
        private RouterInterface $router
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 10]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Check only for POST login requests
        if ($request->attributes->get('_route') !== 'app_user_login' || !$request->isMethod('POST')) {
            return;
        }

        $captchaToken = $request->request->get('g-recaptcha-response');

        if (!$this->recaptchaService->verify($captchaToken)) {
            $exception = new \Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException('Veuillez valider le reCAPTCHA pour continuer.');
            $request->getSession()->set(\Symfony\Component\Security\Http\SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
            
            $event->setResponse(new RedirectResponse($this->router->generate('app_user_login')));
        }
    }
}
