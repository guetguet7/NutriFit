<?php

namespace App\Security\Voter;

use App\Entity\Activite;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class ActiviteVoter extends Voter
{
    public const VIEW = 'ACTIVITE_VIEW';
    public const EDIT = 'ACTIVITE_EDIT';
    public const DELETE = 'ACTIVITE_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof Activite;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Activite $activite */
        $activite = $subject;

        return $activite->getUtilisateur()?->getId() === $user->getId();
    }
}
