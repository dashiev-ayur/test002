<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ShopRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShopRepository::class)]
#[ORM\Table(name: 'shops')]
class Shop
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private string $name = '';

    /** @var Collection<int, ShopOrder> */
    #[ORM\OneToMany(targetEntity: ShopOrder::class, mappedBy: 'shop', orphanRemoval: true)]
    private Collection $orders;

    #[ORM\OneToOne(targetEntity: TelegramIntegration::class, mappedBy: 'shop', cascade: ['persist', 'remove'])]
    private ?TelegramIntegration $telegramIntegration = null;

    /** @var Collection<int, TelegramSendLog> */
    #[ORM\OneToMany(targetEntity: TelegramSendLog::class, mappedBy: 'shop', orphanRemoval: true)]
    private Collection $telegramSendLogs;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
        $this->telegramSendLogs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /** @return Collection<int, ShopOrder> */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(ShopOrder $order): self
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setShop($this);
        }

        return $this;
    }

    public function removeOrder(ShopOrder $order): self
    {
        $this->orders->removeElement($order);

        return $this;
    }

    public function getTelegramIntegration(): ?TelegramIntegration
    {
        return $this->telegramIntegration;
    }

    /** @return Collection<int, TelegramSendLog> */
    public function getTelegramSendLogs(): Collection
    {
        return $this->telegramSendLogs;
    }

    public function addTelegramSendLog(TelegramSendLog $log): self
    {
        if (!$this->telegramSendLogs->contains($log)) {
            $this->telegramSendLogs->add($log);
            $log->setShop($this);
        }

        return $this;
    }

    public function removeTelegramSendLog(TelegramSendLog $log): self
    {
        $this->telegramSendLogs->removeElement($log);

        return $this;
    }
}
