<?php

namespace App\Repository;

use App\Entity\NotificationPreference;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NotificationPreference>
 */
class NotificationPreferenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationPreference::class);
    }

    public function getOrCreateForUser(User $user): NotificationPreference
    {
        $pref = $this->findOneBy(['user' => $user]);
        if (!$pref) {
            $pref = new NotificationPreference();
            $pref->setUser($user);
            $this->getEntityManager()->persist($pref);
            $this->getEntityManager()->flush();
        }
        return $pref;
    }
}
