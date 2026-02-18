<?php

namespace App\Repository;

use App\Entity\Repas;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Repas>
 */
final class RepasRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Repas::class);
    }

    /**
     * @return Repas[]
     */
    public function findByUserAndPeriod(User $user, 
    ?\DateTimeImmutable $start, 
    ?\DateTimeImmutable $end): array
    {
        // construction de la requête pour récupérer les repas de l'utilisateur dans la période spécifiée
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.utilisateur = :user')
            ->setParameter('user', $user)
            ->orderBy('r.dateRepas', 'DESC');

        // ajout des conditions de date si elles sont fournies
        if ($start !== null) {
            $qb->andWhere('r.dateRepas >= :start')
                ->setParameter('start', $start);
        }
        // ajout de la condition de date de fin si elle est fournie
        if ($end !== null) {
            $qb->andWhere('r.dateRepas <= :end')
                ->setParameter('end', $end);
        }
    // exécution de la requête et retour des résultats
        return $qb->getQuery()->getResult();
    }
}
