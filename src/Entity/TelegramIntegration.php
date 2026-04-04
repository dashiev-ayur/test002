<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TelegramIntegrationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TelegramIntegrationRepository::class)]
#[ORM\Table(name: 'telegram_integrations')]
#[ORM\UniqueConstraint(name: 'telegram_integrations_shop_unique', columns: ['shop_id'])]
#[ORM\HasLifecycleCallbacks]
class TelegramIntegration
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'telegramIntegration')]
    #[ORM\JoinColumn(name: 'shop_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Shop $shop = null;

    #[ORM\Column(type: 'text')]
    private string $botToken = '';

    #[ORM\Column(type: 'text')]
    private string $chatId = '';

    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

    #[ORM\Column(type: 'datetimetz_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetimetz_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

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

    public function getBotToken(): string
    {
        return $this->botToken;
    }

    public function setBotToken(string $botToken): self
    {
        $this->botToken = $botToken;

        return $this;
    }

    public function getChatId(): string
    {
        return $this->chatId;
    }

    public function setChatId(string $chatId): self
    {
        $this->chatId = $chatId;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function markCreated(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function markUpdated(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
