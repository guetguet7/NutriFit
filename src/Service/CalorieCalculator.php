<?php

namespace App\Service;

use App\Entity\ProfilUtilisateur;

final class CalorieCalculator
{
    public function getTargetKcal(ProfilUtilisateur $profil): int
    {
        $age = $profil->getAge();
        $tailleCm = $profil->getTailleCm();
        $poidsKg = $profil->getPoidsKg();
        $sexe = $profil->getSexe();

        if ($age === null || $tailleCm === null || $poidsKg === null || $sexe === null) {
            return 2000;
        }

        $poids = (float) $poidsKg;
        $bmr = (10 * $poids) + (6.25 * $tailleCm) - (5 * $age);
        $bmr += $sexe === 'homme' ? 5 : -161;

        $activityFactor = match ($profil->getNiveauActivite()) {
            'sédentaire' => 1.2,
            'léger' => 1.375,
            'modéré' => 1.55,
            'actif' => 1.725,
            default => 1.2,
        };

        $tdee = $bmr * $activityFactor;
        $delta = match ($profil->getObjectifType()) {
            'perte' => -500,
            'prise' => 300,
            default => 0,
        };

        return max(0, (int) round($tdee + $delta));
    }
}
