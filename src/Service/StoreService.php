<?php

namespace App\Service;

use App\Domain\Entity\Store;
use App\Domain\Repository\StoreRepository;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Response;

class StoreService
{
    public function __construct(
        private readonly StoreRepository $storeRepository,
        private readonly ValidatorInterface $validator
    ) {}

    public function getAll(): array
    {
        return $this->storeRepository->findAllOrdered();
    }

    public function getStore(Store $store): Store
    {
        return $store;
    }

    public function create(array $data): Store
    {
        $store = new Store();
        $store->setCode($data['code'] ?? null);

        $errors = $this->validator->validate($store);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors, Response::HTTP_BAD_REQUEST);
        }

        $this->storeRepository->save($store);

        return $store;
    }

    public function update(Store $store, array $data): Store
    {
        $store->setCode($data['code'] ?? null);

        $errors = $this->validator->validate($store);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors, Response::HTTP_BAD_REQUEST);
        }

        $this->storeRepository->save($store);

        return $store;
    }

    public function delete(Store $store): void
    {
        $this->storeRepository->remove($store);
    } 
    
    public function getStoreById(int $storeId): Store
     {
        $store = $this->storeRepository->find($storeId);
        if (!$store) {
            throw new \InvalidArgumentException('Склад с таким id не найден', Response::HTTP_BAD_REQUEST);
        }

        return $store;
    }
}
