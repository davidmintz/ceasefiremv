<?php

namespace App\Repository;

use App\Entity\Rsvp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rsvp>
 */
class RsvpRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rsvp::class);
    }

    public function findOneByMeetingAndEmail(string $meeting, string $email): ?Rsvp
    {
        return $this->findOneBy(['meeting' => $meeting, 'email' => Rsvp::normalizeEmail($email)]);
    }

    public function findOneByRawToken(string $rawToken): ?Rsvp
    {
        return $this->findOneBy(['tokenHash' => Rsvp::hashToken($rawToken)]);
    }
}
