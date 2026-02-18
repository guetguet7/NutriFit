<?php

namespace App\Repository;

use App\Entity\Activite;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Activite>
 */
final class ActiviteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activite::class);
    }

    /**
     * @return Activite[]
     */
    //la méthode "findByUserAndPeriod" permet de récupérer les activités d'un utilisateur sur une période donnée, en filtrant les résultats par date et en les triant par date d'activité de manière descendante pour afficher les activités les plus récentes en premier.
    public function findByUserAndPeriod(User $user, ?\DateTimeImmutable $start, ?\DateTimeImmutable $end): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.utilisateur = :user')
            ->setParameter('user', $user)
            ->orderBy('a.dateActivite', 'DESC');

        if ($start !== null) {
            $qb->andWhere('a.dateActivite >= :start')
                ->setParameter('start', $start);
        }

        if ($end !== null) {
            $qb->andWhere('a.dateActivite <= :end')
                ->setParameter('end', $end);
        }

        return $qb->getQuery()->getResult();
    }
}
