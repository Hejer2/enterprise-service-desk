<?php

namespace App\Repository;

use App\Entity\CsatRating;
use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CsatRating>
 */
class CsatRatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CsatRating::class);
    }

    public function findByTicketAndUser(Ticket $ticket, User $user): ?CsatRating
    {
        return $this->findOneBy([
            'ticket' => $ticket,
            'user' => $user,
        ]);
    }
}
