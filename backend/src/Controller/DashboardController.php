<?php

namespace App\Controller;

use App\Entity\AuditLog;
use App\Entity\Secret;
use App\Entity\User;
use App\Entity\Vault;
use App\Entity\VaultMember;
use App\Repository\VaultRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private VaultRepository $vaultRepository,
    ) {
    }

    #[Route('/api/dashboard', name: 'api_dashboard', methods: ['GET'])]
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $owned = $this->vaultRepository->findBy(['owner' => $user]);
        $memberEntries = $this->em->getRepository(VaultMember::class)->findBy(['author' => $user]);
        $shared = array_map(fn (VaultMember $m) => $m->getVault(), $memberEntries);

        $allVaults = [];
        foreach (array_merge($owned, $shared) as $v) {
            $allVaults[$v->getId()] = $v;
        }
        $allVaults = array_values($allVaults);

        // Stats
        $vaultsCount = count($allVaults);
        $secretsCount = 0;
        foreach ($allVaults as $vault) {
            $secretsCount += $this->em->getRepository(Secret::class)->count(['vault' => $vault]);
        }

        $accessCount = 0;
        if (!empty($allVaults)) {
            $qb = $this->em->createQueryBuilder();
            $accessCount = $qb->select('COUNT(l.id)')
                ->from(AuditLog::class, 'l')
                ->where('l.vault IN (:vaults)')
                ->andWhere('l.action = :action')
                ->setParameter('vaults', $allVaults)
                ->setParameter('action', 'secret.accessed')
                ->getQuery()
                ->getSingleScalarResult();
        }

        // Recent vaults (3 most recently active by audit log)
        $recentVaults = [];
        if (!empty($allVaults)) {
            $qb = $this->em->createQueryBuilder();
            $rows = $qb->select('IDENTITY(l.vault) AS vault_id, MAX(l.createdAt) AS last_activity')
                ->from(AuditLog::class, 'l')
                ->where('l.vault IN (:vaults)')
                ->setParameter('vaults', $allVaults)
                ->groupBy('l.vault')
                ->orderBy('last_activity', 'DESC')
                ->setMaxResults(3)
                ->getQuery()
                ->getResult();

            $vaultMap = [];
            foreach ($allVaults as $v) {
                $vaultMap[$v->getId()] = $v;
            }

            foreach ($rows as $row) {
                $v = $vaultMap[(int) $row['vault_id']] ?? null;
                if ($v) {
                    $secretCount = $this->em->getRepository(Secret::class)->count(['vault' => $v]);
                    $lastLog = $this->em->getRepository(AuditLog::class)->findOneBy(
                        ['vault' => $v],
                        ['createdAt' => 'DESC'],
                    );
                    $recentVaults[] = [
                        'id' => $v->getId(),
                        'name' => $v->getName(),
                        'description' => $v->getDescription(),
                        'secretsCount' => $secretCount,
                        'membersCount' => $v->getMembers()->count(),
                        'lastActivityAt' => $lastLog?->getCreatedAt()?->format(\DateTimeInterface::ATOM),
                    ];
                }
            }
        }

        // If fewer than 3 from logs, fill with remaining vaults
        if (count($recentVaults) < 3 && count($allVaults) > count($recentVaults)) {
            $usedIds = array_map(fn ($v) => $v['id'], $recentVaults);
            foreach ($allVaults as $v) {
                if (count($recentVaults) >= 3) break;
                if (in_array($v->getId(), $usedIds)) continue;
                $secretCount = $this->em->getRepository(Secret::class)->count(['vault' => $v]);
                $recentVaults[] = [
                    'id' => $v->getId(),
                    'name' => $v->getName(),
                    'description' => $v->getDescription(),
                    'secretsCount' => $secretCount,
                    'membersCount' => $v->getMembers()->count(),
                    'lastActivityAt' => null,
                ];
            }
        }

        // Recent logs (5 latest)
        $recentLogs = [];
        if (!empty($allVaults)) {
            $logs = $this->em->getRepository(AuditLog::class)->findBy(
                ['vault' => $allVaults],
                ['createdAt' => 'DESC'],
                5,
            );

            $recentLogs = array_map(fn (AuditLog $l) => [
                'id' => $l->getId(),
                'action' => $l->getAction(),
                'performedBy' => $l->getPerformedBy()?->getEmail(),
                'vaultName' => $l->getVault()?->getName(),
                'secretName' => $l->getSecret()?->getName(),
                'createdAt' => $l->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            ], $logs);
        }

        return $this->json([
            'stats' => [
                'vaultsCount' => $vaultsCount,
                'secretsCount' => $secretsCount,
                'accessCount' => (int) $accessCount,
            ],
            'recentVaults' => $recentVaults,
            'recentLogs' => $recentLogs,
        ]);
    }
}
