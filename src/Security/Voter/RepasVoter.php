<?php

namespace App\Security\Voter;

use App\Entity\Repas;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class RepasVoter extends Voter
{
    public const VIEW = 'REPAS_VIEW';
    public const EDIT = 'REPAS_EDIT';
    public const DELETE = 'REPAS_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof Repas;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Repas $repas */
        $repas = $subject;

        return $repas->getUtilisateur()?->getId() === $user->getId();
    }
}
