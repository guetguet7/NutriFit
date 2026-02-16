<?php

namespace App\Security\Voter;

use App\Entity\ProfilUtilisateur;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class ProfilUtilisateurVoter extends Voter
{
    public const VIEW = 'PROFIL_VIEW';
    public const EDIT = 'PROFIL_EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT], true)
            && $subject instanceof ProfilUtilisateur;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var ProfilUtilisateur $profil */
        $profil = $subject;
        $owner = $profil->getUtilisateur();

        // Allow editing a new profile not yet linked to a user (current user is set on save).
        if ($owner === null) {
            return true;
        }

        return $owner->getId() === $user->getId();
    }
}
