<?php

namespace App\Service;

use App\Domain\Entity\Product;
use App\Domain\Repository\ProductRepository;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Response;
use App\Service\StoreService;

class ProductService
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly StoreService $storeService,
        private readonly ValidatorInterface $validator
    ) {}

    public function getAll(): array
    {
        return $this->productRepository->findAll();
    }

    public function getById(Product $product): Product
    {
        return $product;
    }

    public function create(array $data): Product
    {
        $product = new Product();
        $product->setCode($data['code'] ?? null);

        if (!empty($data['store_id'])) {
            $store = $this->storeService->getStoreById($data['store_id']);
            $product->setStore($store);
        }

        $errors = $this->validator->validate($product);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors, Response::HTTP_BAD_REQUEST);
        }

        $this->productRepository->save($product);

        return $product;
    }

    public function update(Product $product, array $data): Product
    {
        $product->setCode($data['code'] ?? null);

        if (!empty($data['store_id'])) {
            $store = $this->storeService->getStoreById($data['store_id']);
            $product->setStore($store);
        }

        $errors = $this->validator->validate($product);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors, Response::HTTP_BAD_REQUEST);
        }

        $this->productRepository->save($product);

        return $product;
    }

    public function delete(Product $store): void
    {
        $this->productRepository->remove($store);
    }
}
