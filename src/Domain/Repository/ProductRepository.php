<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Product;
use App\Domain\Entity\Store;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(protected EntityManagerInterface $entityManager, ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function save(Product $product, bool $flush = true): void
    {
        $this->entityManager->persist($product);
        if ($flush) {
            $this->entityManager->flush();
        }
    }

     /**
     *
     * @param int $storeId
     * @return Product[]
     */
    public function findByStoreId(int $storeId): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.store', 's')
            ->andWhere('s.id = :storeId')
            ->setParameter('storeId', $storeId)
            ->getQuery()
            ->getResult();
    }
}
