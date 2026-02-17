<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\Secret;
use App\Entity\User;
use App\Entity\Vault;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AuditLogger
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
    ) {
    }

    public function log(
        string $action,
        User $user,
        ?Vault $vault = null,
        ?Secret $secret = null,
    ): void {
        $request = $this->requestStack->getCurrentRequest();

        $log = new AuditLog();
        $log->setAction($action);
        $log->setPerformedBy($user);
        $log->setVault($vault);
        $log->setSecret($secret);
        $log->setIpAddress($request?->getClientIp());
        $log->setUserAgent(substr($request?->headers->get('User-Agent', '') ?? '', 0, 500));
        $log->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($log);
        $this->em->flush();
    }
}
