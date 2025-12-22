<?php

namespace App\Domain\Repository;

interface FeedRepositoryInterface
{
    public function ensureFeed(int $userId, int $count): array;
}