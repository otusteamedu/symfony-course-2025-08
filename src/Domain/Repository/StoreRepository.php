<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Store;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Store>
 */
class StoreRepository extends ServiceEntityRepository
{
    public function __construct(protected EntityManagerInterface $entityManager, ManagerRegistry $registry)
    {
        parent::__construct($registry, Store::class);
    }

    public function save(Store $store, bool $flush = true): void
    {
        $this->entityManager->persist($store);
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     *
     * @param string $code
     * @param int $minProducts
     * @return Store[]
     */
    public function findByCodeWithMinProducts(string $code, int $minProducts): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.products', 'p')
            ->addSelect('COUNT(p.id) AS HIDDEN productCount')
            ->andWhere('s.code = :code')
            ->setParameter('code', $code)
            ->groupBy('s.id')
            ->having('COUNT(p.id) >= :minProducts')
            ->setParameter('minProducts', $minProducts)
            ->getQuery()
            ->getResult();
    }
}
