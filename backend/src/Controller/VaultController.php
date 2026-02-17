<?php

namespace App\Controller;

use App\Entity\AuditLog;
use App\Entity\Secret;
use App\Entity\User;
use App\Entity\Vault;
use App\Entity\VaultMember;
use App\Repository\UserRepository;
use App\Repository\VaultRepository;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/vaults')]
class VaultController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private VaultRepository $vaultRepository,
        private UserRepository $userRepository,
        private AuditLogger $auditLogger,
    ) {
    }

    #[Route('', name: 'api_vaults_list', methods: ['GET'])]
    public function list(): JsonResponse
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

        $data = array_map(fn (Vault $v) => $this->serializeVault($v, $user), $allVaults);

        return $this->json(array_values($data));
    }

    #[Route('', name: 'api_vaults_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        $name = trim($data['name'] ?? '');
        if ($name === '') {
            return $this->json(['error' => 'Le nom est requis.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $vault = new Vault();
        $vault->setName($name);
        $vault->setDescription($data['description'] ?? null);
        $vault->setOwner($user);

        $member = new VaultMember();
        $member->setVault($vault);
        $member->setAuthor($user);
        $member->setRole('owner');
        $member->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($vault);
        $this->em->persist($member);
        $this->em->flush();

        $this->auditLogger->log('vault.created', $user, $vault);

        return $this->json($this->serializeVault($vault, $user), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_vaults_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $vault = $this->findVaultOrFail($id);
        if ($vault instanceof JsonResponse) {
            return $vault;
        }

        /** @var User $user */
        $user = $this->getUser();

        return $this->json($this->serializeVault($vault, $user));
    }

    // --- Secrets ---

    #[Route('/{id}/secrets', name: 'api_vaults_secrets_list', methods: ['GET'])]
    public function listSecrets(int $id): JsonResponse
    {
        $vault = $this->findVaultOrFail($id);
        if ($vault instanceof JsonResponse) {
            return $vault;
        }

        $secrets = $this->em->getRepository(Secret::class)->findBy(
            ['vault' => $vault],
            ['createdAt' => 'DESC'],
        );

        $data = array_map(fn (Secret $s) => [
            'id' => $s->getId(),
            'name' => $s->getName(),
            'description' => $s->getDescription(),
            'createdAt' => $s->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $s->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
            'lastAccessedAt' => $s->getLastAccessedAt()?->format(\DateTimeInterface::ATOM),
        ], $secrets);

        return $this->json($data);
    }

    #[Route('/{id}/secrets', name: 'api_vaults_secrets_create', methods: ['POST'])]
    public function createSecret(int $id, Request $request): JsonResponse
    {
        $vault = $this->findVaultOrFail($id);
        if ($vault instanceof JsonResponse) {
            return $vault;
        }

        $data = json_decode($request->getContent(), true);

        $name = trim($data['name'] ?? '');
        if ($name === '') {
            return $this->json(['error' => 'Le nom est requis.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $value = $data['value'] ?? '';
        if ($value === '') {
            return $this->json(['error' => 'La valeur est requise.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $secret = new Secret();
        $secret->setName($name);
        $secret->setDescription($data['description'] ?? null);
        $secret->setEncryptedValue($value);
        $secret->setVault($vault);
        $secret->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($secret);
        $this->em->flush();

        /** @var User $user */
        $user = $this->getUser();
        $this->auditLogger->log('secret.created', $user, $vault, $secret);

        return $this->json([
            'id' => $secret->getId(),
            'name' => $secret->getName(),
            'description' => $secret->getDescription(),
            'createdAt' => $secret->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => null,
            'lastAccessedAt' => null,
        ], Response::HTTP_CREATED);
    }

    #[Route('/{vaultId}/secrets/{secretId}', name: 'api_vaults_secrets_value', methods: ['GET'])]
    public function getSecretValue(int $vaultId, int $secretId): JsonResponse
    {
        $vault = $this->findVaultOrFail($vaultId);
        if ($vault instanceof JsonResponse) {
            return $vault;
        }

        $secret = $this->em->getRepository(Secret::class)->findOneBy([
            'id' => $secretId,
            'vault' => $vault,
        ]);

        if (!$secret) {
            return $this->json(['error' => 'Secret introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $secret->setLastAccessedAt(new \DateTimeImmutable());
        $this->em->flush();

        /** @var User $user */
        $user = $this->getUser();
        $this->auditLogger->log('secret.accessed', $user, $vault, $secret);

        return $this->json([
            'id' => $secret->getId(),
            'name' => $secret->getName(),
            'value' => $secret->getEncryptedValue(),
        ]);
    }

    // --- Members ---

    #[Route('/{id}/members', name: 'api_vaults_members_list', methods: ['GET'])]
    public function listMembers(int $id): JsonResponse
    {
        $vault = $this->findVaultOrFail($id);
        if ($vault instanceof JsonResponse) {
            return $vault;
        }

        $members = $this->em->getRepository(VaultMember::class)->findBy(['vault' => $vault]);

        $data = array_map(fn (VaultMember $m) => [
            'id' => $m->getId(),
            'email' => $m->getAuthor()->getEmail(),
            'role' => $m->getRole(),
            'createdAt' => $m->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ], $members);

        return $this->json($data);
    }

    #[Route('/{id}/members', name: 'api_vaults_members_add', methods: ['POST'])]
    public function addMember(int $id, Request $request): JsonResponse
    {
        $vault = $this->findVaultOrFail($id);
        if ($vault instanceof JsonResponse) {
            return $vault;
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if ($vault->getOwner() !== $currentUser) {
            return $this->json(['error' => 'Seul le propriétaire peut ajouter des membres.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $email = trim($data['email'] ?? '');
        $role = $data['role'] ?? 'member';

        if ($email === '') {
            return $this->json(['error' => "L'email est requis."], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $targetUser = $this->userRepository->findOneBy(['email' => $email]);
        if (!$targetUser) {
            return $this->json(['error' => 'Aucun utilisateur trouvé avec cet email.'], Response::HTTP_NOT_FOUND);
        }

        $existing = $this->em->getRepository(VaultMember::class)->findOneBy([
            'vault' => $vault,
            'author' => $targetUser,
        ]);
        if ($existing) {
            return $this->json(['error' => 'Cet utilisateur est déjà membre du coffre.'], Response::HTTP_CONFLICT);
        }

        $member = new VaultMember();
        $member->setVault($vault);
        $member->setAuthor($targetUser);
        $member->setRole($role);
        $member->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($member);
        $this->em->flush();

        $this->auditLogger->log('member.added', $currentUser, $vault);

        return $this->json([
            'id' => $member->getId(),
            'email' => $targetUser->getEmail(),
            'role' => $member->getRole(),
            'createdAt' => $member->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ], Response::HTTP_CREATED);
    }

    // --- Logs ---

    #[Route('/{id}/logs', name: 'api_vaults_logs_list', methods: ['GET'])]
    public function listLogs(int $id): JsonResponse
    {
        $vault = $this->findVaultOrFail($id);
        if ($vault instanceof JsonResponse) {
            return $vault;
        }

        $logs = $this->em->getRepository(AuditLog::class)->findBy(
            ['vault' => $vault],
            ['createdAt' => 'DESC'],
        );

        $data = array_map(fn (AuditLog $l) => [
            'id' => $l->getId(),
            'action' => $l->getAction(),
            'performedBy' => $l->getPerformedBy()?->getEmail(),
            'secretName' => $l->getSecret()?->getName(),
            'ipAddress' => $l->getIpAddress(),
            'userAgent' => $l->getUserAgent(),
            'createdAt' => $l->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ], $logs);

        return $this->json($data);
    }

    // --- Global logs ---

    #[Route('/logs/all', name: 'api_vaults_logs_all', methods: ['GET'], priority: 10)]
    public function allLogs(): JsonResponse
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

        if (empty($allVaults)) {
            return $this->json([]);
        }

        $logs = $this->em->getRepository(AuditLog::class)->findBy(
            ['vault' => $allVaults],
            ['createdAt' => 'DESC'],
        );

        $data = array_map(fn (AuditLog $l) => [
            'id' => $l->getId(),
            'action' => $l->getAction(),
            'performedBy' => $l->getPerformedBy()?->getEmail(),
            'vaultName' => $l->getVault()?->getName(),
            'secretName' => $l->getSecret()?->getName(),
            'ipAddress' => $l->getIpAddress(),
            'userAgent' => $l->getUserAgent(),
            'createdAt' => $l->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ], $logs);

        return $this->json($data);
    }

    // --- Helpers ---

    private function findVaultOrFail(int $id): Vault|JsonResponse
    {
        $vault = $this->vaultRepository->find($id);
        if (!$vault) {
            return $this->json(['error' => 'Coffre introuvable.'], Response::HTTP_NOT_FOUND);
        }

        /** @var User $user */
        $user = $this->getUser();

        if ($vault->getOwner() === $user) {
            return $vault;
        }

        $member = $this->em->getRepository(VaultMember::class)->findOneBy([
            'vault' => $vault,
            'author' => $user,
        ]);

        if (!$member) {
            return $this->json(['error' => 'Accès refusé.'], Response::HTTP_FORBIDDEN);
        }

        return $vault;
    }

    private function serializeVault(Vault $vault, User $currentUser): array
    {
        $secretsCount = $this->em->getRepository(Secret::class)->count(['vault' => $vault]);

        $lastLog = $this->em->getRepository(AuditLog::class)->findOneBy(
            ['vault' => $vault],
            ['createdAt' => 'DESC'],
        );

        $role = 'membre';
        if ($vault->getOwner() === $currentUser) {
            $role = 'propriétaire';
        } else {
            $memberEntry = $this->em->getRepository(VaultMember::class)->findOneBy([
                'vault' => $vault,
                'author' => $currentUser,
            ]);
            if ($memberEntry) {
                $role = $memberEntry->getRole();
            }
        }

        return [
            'id' => $vault->getId(),
            'name' => $vault->getName(),
            'description' => $vault->getDescription(),
            'role' => $role,
            'secretsCount' => $secretsCount,
            'membersCount' => $vault->getMembers()->count(),
            'lastActivityAt' => $lastLog?->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
