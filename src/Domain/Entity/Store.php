<?php

namespace App\Domain\Entity;

use App\Domain\Repository\StoreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: StoreRepository::class)]
#[ORM\Table(name: 'store', indexes: [
    new ORM\Index(name: 'idx_store_code', columns: ['code'])
])]
class Store
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 64, unique: true)]
    #[Assert\NotBlank(message: "Код склада обязателен")]
    private string $code;

    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }
}