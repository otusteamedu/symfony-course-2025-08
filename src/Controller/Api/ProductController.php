<?php

namespace App\Controller\Api;

use App\Domain\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use App\Domain\Repository\ProductRepository;
use App\Domain\Repository\StoreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/products')]
class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly StoreRepository   $storeRepository,
        private readonly EntityManagerInterface $em
    ) {}

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $products = $this->productRepository->findAll();
        return $this->json($products);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(Product $product): JsonResponse
    {
        return $this->json($product);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $store = $this->storeRepository->find($data['store_id']);

        if (!$store) {
            return $this->json(['error' => 'Store not found'], 404);
        }

        $product = new Product();
        $product->setCode($data['code']);
        $product->setStore($store);

        $this->em->persist($product);
        $this->em->flush();

        return $this->json($product, 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(Product $product, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['code'])) {
            $product->setCode($data['code']);
        }

        if (isset($data['store_id'])) {
            $store = $this->storeRepository->find($data['store_id']);
            if ($store) {
                $product->setStore($store);
            }
        }

        $this->em->flush();

        return $this->json($product);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(Product $product): JsonResponse
    {
        $this->em->remove($product);
        $this->em->flush();

        return $this->json(null, 204);
    }
}