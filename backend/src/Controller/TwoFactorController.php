<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\TotpService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/2fa')]
class TwoFactorController extends AbstractController
{
    public function __construct(
        private TotpService $totpService,
        private EntityManagerInterface $em,
        private JWTTokenManagerInterface $jwtManager,
    ) {
    }

    #[Route('/verify', name: 'api_2fa_verify', methods: ['POST'])]
    public function verify(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $code = $data['code'] ?? '';

        if (!$this->totpService->verifyCode($user, $code)) {
            return $this->json(['error' => 'Invalid TOTP code.'], Response::HTTP_UNAUTHORIZED);
        }

        $jwt = $this->jwtManager->create($user);

        return $this->json(['token' => $jwt]);
    }

    #[Route('/enable', name: 'api_2fa_enable', methods: ['POST'])]
    public function enable(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isTwoFactorEnabled()) {
            return $this->json(['error' => '2FA is already enabled.'], Response::HTTP_BAD_REQUEST);
        }

        $totp = $this->totpService->generateSecret();
        $user->setTwoFactorSecret($totp->getSecret());
        $this->em->flush();

        return $this->json([
            'secret' => $totp->getSecret(),
            'provisioning_uri' => $this->totpService->getProvisioningUri($user),
        ]);
    }

    #[Route('/confirm', name: 'api_2fa_confirm', methods: ['POST'])]
    public function confirm(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $code = $data['code'] ?? '';

        if (!$user->getTwoFactorSecret()) {
            return $this->json(['error' => 'Call /api/2fa/enable first.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->totpService->verifyCode($user, $code)) {
            return $this->json(['error' => 'Invalid TOTP code.'], Response::HTTP_UNAUTHORIZED);
        }

        $user->setTwoFactorEnabled(true);
        $this->em->flush();

        return $this->json(['message' => '2FA has been enabled.']);
    }

    #[Route('/disable', name: 'api_2fa_disable', methods: ['POST'])]
    public function disable(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $code = $data['code'] ?? '';

        if (!$user->isTwoFactorEnabled()) {
            return $this->json(['error' => '2FA is not enabled.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->totpService->verifyCode($user, $code)) {
            return $this->json(['error' => 'Invalid TOTP code.'], Response::HTTP_UNAUTHORIZED);
        }

        $user->setTwoFactorEnabled(false);
        $user->setTwoFactorSecret(null);
        $this->em->flush();

        return $this->json(['message' => '2FA has been disabled.']);
    }
}
