<?php

namespace App\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JwtSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            Events::JWT_CREATED => 'onJwtCreated',
        ];
    }

    public function onJwtCreated(JWTCreatedEvent $event): void
    {
        $data = $event->getData();

        if (!($data['2fa_pending'] ?? false)) {
            return;
        }

        $data['exp'] = time() + 300;
        $data['roles'] = ['ROLE_2FA_PENDING'];

        $event->setData($data);
    }
}
