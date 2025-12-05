<?php

namespace App\Controller\Api;

use App\Domain\Entity\Store;
use Doctrine\ORM\EntityManagerInterface;
use App\Domain\Repository\StoreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/stores')]
class StoreController extends AbstractController
{
    public function __construct(
        private readonly StoreRepository $storeRepository,
        private readonly EntityManagerInterface $em
    ) {}

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $stores = $this->storeRepository->findAllOrdered();
        return $this->json($stores);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(Store $store): JsonResponse
    {
        return $this->json($store);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $store = new Store();
        $store->setCode($data['code']);

        $this->em->persist($store);
        $this->em->flush();

        return $this->json($store, 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(Store $store, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $store->setCode($data['code']);
        $this->em->flush();

        return $this->json($store);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(Store $store): JsonResponse
    {
        $this->em->remove($store);
        $this->em->flush();

        return $this->json(null, 204);
    }
}
