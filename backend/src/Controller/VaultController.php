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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/vaults')]
class VaultController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private VaultRepository $vaultRepository,
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

        $allVaults = array_unique(array_merge($owned, $shared), SORT_REGULAR);

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

        return $this->json($this->serializeVault($vault, $user), Response::HTTP_CREATED);
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
