<?php

namespace App\Service;

use App\Entity\User;
use OTPHP\TOTP;

class TotpService
{
    public function generateSecret(): TOTP
    {
        $totp = TOTP::generate();
        $totp->setIssuer('Cipher');
        $totp->setPeriod(30);
        $totp->setDigits(6);

        return $totp;
    }

    public function verifyCode(User $user, string $code): bool
    {
        $secret = $user->getTwoFactorSecret();
        if (!$secret) {
            return false;
        }

        $totp = TOTP::createFromSecret($secret);
        $totp->setPeriod(30);
        $totp->setDigits(6);

        return $totp->verify($code, null, 1);
    }

    public function getProvisioningUri(User $user): string
    {
        $secret = $user->getTwoFactorSecret();
        $totp = TOTP::createFromSecret($secret);
        $totp->setIssuer('Cipher');
        $totp->setPeriod(30);
        $totp->setDigits(6);
        $totp->setLabel($user->getEmail());

        return $totp->getProvisioningUri();
    }
}
