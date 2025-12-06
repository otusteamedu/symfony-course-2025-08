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
     * Найти склад по коду
     */
    public function findByCode(string $code): ?Store
    {
        return $this->findOneBy(['code' => $code]);
    }

    /**
     * Получить все склады отсортированные по коду
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.code', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function remove(Store $store, bool $flush = true): void
    {
        $this->entityManager->remove($store);
        if ($flush) {
            $this->entityManager->flush();
        }
    }
}
