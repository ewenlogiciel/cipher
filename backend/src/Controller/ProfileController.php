<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    #[Route('/api/profile', name: 'api_profile_show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'twoFactorEnabled' => $user->isTwoFactorEnabled(),
            'createdAt' => $user->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ]);
    }

    #[Route('/api/profile', name: 'api_profile_update', methods: ['PUT'])]
    public function update(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        UserRepository $userRepository,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        $email = trim($data['email'] ?? '');
        $currentPassword = $data['currentPassword'] ?? '';
        $newPassword = $data['newPassword'] ?? '';

        // Vérifier le mot de passe actuel
        if ($currentPassword === '') {
            return $this->json(['error' => 'Le mot de passe actuel est requis.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            return $this->json(['error' => 'Le mot de passe actuel est incorrect.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Modifier l'email
        if ($email !== '' && $email !== $user->getEmail()) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->json(['error' => 'Adresse email invalide.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $existing = $userRepository->findOneBy(['email' => $email]);
            if ($existing && $existing->getId() !== $user->getId()) {
                return $this->json(['error' => 'Cette adresse email est déjà utilisée.'], Response::HTTP_CONFLICT);
            }

            $user->setEmail($email);
        }

        // Modifier le mot de passe
        if ($newPassword !== '') {
            if (strlen($newPassword) < 8) {
                return $this->json(['error' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        }

        $user->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'twoFactorEnabled' => $user->isTwoFactorEnabled(),
            'createdAt' => $user->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ]);
    }
}
