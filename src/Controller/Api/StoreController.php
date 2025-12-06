<?php

namespace App\Controller\Api;

use App\Domain\Entity\Store;
use App\Service\StoreService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/stores')]
class StoreController extends AbstractController
{
    public function __construct(
        private readonly StoreService $storeService
    ) {}

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        try {
            $stores = $this->storeService->getAll();
            return $this->json($stores);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(Store $store): JsonResponse
    {
        try {
            return $this->json($this->storeService->getStore($store));
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $store = $this->storeService->create($data);
            return $this->json($store, Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode() ?: Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(Store $store, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $store = $this->storeService->update($store, $data);
            return $this->json($store);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode() ?: Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(Store $store): JsonResponse
    {
        try {
            $this->storeService->delete($store);
            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
