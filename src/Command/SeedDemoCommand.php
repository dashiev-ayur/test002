<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Shop;
use App\Entity\ShopOrder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-demo',
    description: 'Создать демо-магазин и тестовые заказы (один раз, если магазинов ещё нет)',
)]
final class SeedDemoCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $shopRepository = $this->entityManager->getRepository(Shop::class);
        if ($shopRepository->count([]) > 0) {
            $io->warning('В базе уже есть магазины. Сид не выполнен.');

            return Command::SUCCESS;
        }

        $shop = (new Shop())->setName('Мой магазин Posiflora');
        $this->entityManager->persist($shop);

        $demoOrders = [
            ['A-1001', '1500.00', 'Иван'],
            ['A-1002', '2490.00', 'Анна'],
            ['A-1003', '890.50', 'Пётр'],
            ['B-2001', '12000.00', 'ООО Ромашка'],
            ['B-2002', '340.00', null],
            ['C-3001', '5600.75', 'Елена'],
            ['C-3002', '199.00', 'Мария'],
            ['C-3003', '777.00', 'Дмитрий'],
        ];

        foreach ($demoOrders as [$number, $total, $customerName]) {
            $order = (new ShopOrder())
                ->setShop($shop)
                ->setNumber($number)
                ->setTotal($total)
                ->setCustomerName($customerName);
            $this->entityManager->persist($order);
        }

        $this->entityManager->flush();

        $io->success(\sprintf('Создан магазин «%s» и %d заказов.', $shop->getName(), \count($demoOrders)));

        return Command::SUCCESS;
    }
}
