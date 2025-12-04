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
     * Найти продукт по коду
     */
    public function findByCode(string $code): ?Product
    {
        return $this->findOneBy(['code' => $code]);
    }

    /**
     * Получить все продукты конкретного магазина
     */
    public function findByStore(Store $store): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.store = :store')
            ->setParameter('store', $store)
            ->orderBy('p.code', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
