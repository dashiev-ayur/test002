<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ShopOrderRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Заказ магазина. Имя класса не Order — зарезервировано в DQL.
 */
#[ORM\Entity(repositoryClass: ShopOrderRepository::class)]
#[ORM\Table(
    name: 'orders',
    indexes: [new ORM\Index(name: 'idx_orders_shop_id', columns: ['shop_id'])],
)]
#[ORM\HasLifecycleCallbacks]
class ShopOrder
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(name: 'shop_id', nullable: false, onDelete: 'CASCADE')]
    private ?Shop $shop = null;

    #[ORM\Column(type: 'text')]
    private string $number = '';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $total = '0.00';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $customerName = null;

    #[ORM\Column(type: 'datetimetz_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShop(): ?Shop
    {
        return $this->shop;
    }

    public function setShop(?Shop $shop): self
    {
        $this->shop = $shop;

        return $this;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getTotal(): string
    {
        return $this->total;
    }

    public function setTotal(string $total): self
    {
        $this->total = $total;

        return $this;
    }

    public function getCustomerName(): ?string
    {
        return $this->customerName;
    }

    public function setCustomerName(?string $customerName): self
    {
        $this->customerName = $customerName;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function touchCreatedAt(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }
}
