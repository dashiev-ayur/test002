<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enum\TelegramSendStatus;
use App\Repository\TelegramSendLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TelegramSendLogRepository::class)]
#[ORM\Table(name: 'telegram_send_log')]
#[ORM\Index(name: 'idx_telegram_send_log_shop_id', columns: ['shop_id'])]
#[ORM\Index(name: 'idx_telegram_send_log_order_id', columns: ['order_id'])]
#[ORM\UniqueConstraint(name: 'telegram_send_log_shop_order_unique', columns: ['shop_id', 'order_id'])]
#[ORM\HasLifecycleCallbacks]
class TelegramSendLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'telegramSendLogs')]
    #[ORM\JoinColumn(name: 'shop_id', nullable: false, onDelete: 'CASCADE')]
    private ?Shop $shop = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?ShopOrder $order = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message = null;

    #[ORM\Column(enumType: TelegramSendStatus::class)]
    private TelegramSendStatus $status = TelegramSendStatus::FAILED;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $error = null;

    #[ORM\Column(type: 'datetimetz_immutable')]
    private ?\DateTimeImmutable $sentAt = null;

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

    public function getOrder(): ?ShopOrder
    {
        return $this->order;
    }

    public function setOrder(?ShopOrder $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getStatus(): TelegramSendStatus
    {
        return $this->status;
    }

    public function setStatus(TelegramSendStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): self
    {
        $this->error = $error;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeImmutable $sentAt): self
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function touchSentAt(): void
    {
        if ($this->sentAt === null) {
            $this->sentAt = new \DateTimeImmutable();
        }
    }
}
