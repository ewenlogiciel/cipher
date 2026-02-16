<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authenticator\Token\JWTPostAuthenticationToken;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\AuthenticationTokenCreatedEvent;

class JwtSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            Events::JWT_CREATED => 'onJwtCreated',
            AuthenticationTokenCreatedEvent::class => 'onTokenCreated',
        ];
    }

    public function onJwtCreated(JWTCreatedEvent $event): void
    {
        $data = $event->getData();
        $user = $event->getUser();

        if ($user instanceof User) {
            $data['2fa_enabled'] = $user->isTwoFactorEnabled() ?? false;
        }

        if ($data['2fa_pending'] ?? false) {
            $data['exp'] = time() + 300;
            $data['roles'] = ['ROLE_2FA_PENDING'];
        }

        $event->setData($data);
    }

    public function onTokenCreated(AuthenticationTokenCreatedEvent $event): void
    {
        $token = $event->getAuthenticatedToken();

        if (!$token instanceof JWTPostAuthenticationToken) {
            return;
        }

        $passport = $event->getPassport();
        $payload = $passport->getAttribute('payload', []);

        if (!($payload['2fa_pending'] ?? false)) {
            return;
        }

        $newToken = new JWTPostAuthenticationToken(
            $token->getUser(),
            $token->getFirewallName(),
            ['ROLE_2FA_PENDING'],
            $token->getCredentials(),
        );

        $event->setAuthenticatedToken($newToken);
    }
}
