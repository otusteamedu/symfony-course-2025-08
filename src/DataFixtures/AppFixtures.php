<?php

namespace App\DataFixtures;

use App\Domain\Entity\Store;
use App\Domain\Entity\Product;
use App\Domain\Repository\StoreRepository;
use App\Domain\Repository\ProductRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly StoreRepository $storeRepository,
        private readonly ProductRepository $productRepository
    )
    {
    }

    public function load(ObjectManager $manager): void
    {
        $stores = [];

        // Создаём 5 складов
        for ($i = 1; $i <= 5; $i++) {
            $store = new Store();
            $store->setCode('store_' . $i);

            $this->storeRepository->save($store, false);
            $stores[] = $store;
        }

        // Создаём 30 продуктов
        for ($i = 1; $i <= 30; $i++) {
            $product = new Product();
            $product->setCode('product_' . $i);

            // Случайный склад
            $randomStore = $stores[array_rand($stores)];
            $product->setStore($randomStore);

            $this->productRepository->save($product, false);
        }

        // Один общий flush
        $manager->flush();
    }
}
