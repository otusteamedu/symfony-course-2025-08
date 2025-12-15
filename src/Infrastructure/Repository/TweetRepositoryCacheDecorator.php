<?php

namespace App\Infrastructure\Repository;

use App\Application\Symfony\AdapterCountingDecorator;
use App\Domain\Entity\Tweet;
use App\Domain\Model\TweetModel;
use App\Domain\Repository\TweetRepositoryInterface;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use StatsdBundle\Storage\MetricsStorageInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

readonly class TweetRepositoryCacheDecorator implements TweetRepositoryInterface
{
    public function __construct(
        private TweetRepository $tweetRepository,
        private TagAwareCacheInterface $cache,
        private MetricsStorageInterface $metricsStorage,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function create(Tweet $tweet): int
    {
        $result = $this->tweetRepository->create($tweet);
        $this->cache->invalidateTags([$this->getCacheTag()]);

        return $result;
    }

    private function getCacheTag(): string
    {
        return 'tweets';
    }

    /**
     * @return TweetModel[]
     * @throws InvalidArgumentException
     * @throws CacheException
     */
    public function getTweetsPaginated(int $page, int $perPage): array
    {
        $cacheKey = $this->getCacheKey($page, $perPage);
        $results = $this->cache->get(
            $cacheKey,
            function (ItemInterface $item) use ($page, $perPage) {
                $tweets = $this->tweetRepository->getTweetsPaginated($page, $perPage);
                $tweetModels = array_map(
                    static fn(Tweet $tweet): TweetModel => new TweetModel(
                        $tweet->getId(),
                        $tweet->getAuthor()->getLogin(),
                        $tweet->getAuthor()->getId(),
                        $tweet->getText(),
                        $tweet->getCreatedAt(),
                    ),
                    $tweets
                );
                $item->set($tweetModels);
                $item->tag($this->getCacheTag());

                return $tweetModels;
            },
            null,
            $metadata
        );

        $metric = [] !== $metadata ? AdapterCountingDecorator::CACHE_HIT_PREFIX : AdapterCountingDecorator::CACHE_MISS_PREFIX;
        $this->metricsStorage->increment($metric . $cacheKey);

        return $results;
    }

    private function getCacheKey(int $page, int $perPage): string
    {
        return "tweets_{$page}_$perPage";
    }
}