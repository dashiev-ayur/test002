<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Enum\TelegramSendStatus;
use App\Entity\TelegramSendLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Журнал попыток отправки в Telegram: агрегаты для экрана статуса.
 *
 * @extends ServiceEntityRepository<TelegramSendLog>
 */
class TelegramSendLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramSendLog::class);
    }

    /**
     * Число записей с указанным статусом за интервал [since, now).
     */
    public function countByShopAndStatusSince(int $shopId, TelegramSendStatus $status, \DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->join('l.shop', 's')
            ->andWhere('s.id = :shopId')
            ->andWhere('l.status = :status')
            ->andWhere('l.sentAt >= :since')
            ->setParameter('shopId', $shopId)
            ->setParameter('status', $status)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Время последней успешной отправки (SENT) для магазина.
     */
    public function findLatestSuccessfulSentAt(int $shopId): ?\DateTimeImmutable
    {
        $row = $this->createQueryBuilder('l')
            ->join('l.shop', 's')
            ->andWhere('s.id = :shopId')
            ->andWhere('l.status = :status')
            ->setParameter('shopId', $shopId)
            ->setParameter('status', TelegramSendStatus::SENT)
            ->orderBy('l.sentAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $row instanceof TelegramSendLog ? $row->getSentAt() : null;
    }

    /**
     * Время последней попытки отправки (любой статус).
     */
    public function findLatestSentAt(int $shopId): ?\DateTimeImmutable
    {
        $row = $this->createQueryBuilder('l')
            ->join('l.shop', 's')
            ->andWhere('s.id = :shopId')
            ->setParameter('shopId', $shopId)
            ->orderBy('l.sentAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $row instanceof TelegramSendLog ? $row->getSentAt() : null;
    }

    public function findOneByShopIdAndOrderId(int $shopId, int $orderId): ?TelegramSendLog
    {
        return $this->createQueryBuilder('l')
            ->join('l.shop', 's')
            ->join('l.order', 'o')
            ->andWhere('s.id = :shopId')
            ->andWhere('o.id = :orderId')
            ->setParameter('shopId', $shopId)
            ->setParameter('orderId', $orderId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
