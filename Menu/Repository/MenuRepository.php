<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\Repository;

use Aurora\Module\Editorial\Menu\Entity\Menu;
use Aurora\Module\Editorial\Menu\Entity\MenuInterface;
use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<MenuInterface>
 */
class MenuRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Menu::class, MenuInterface::class);
    }

    /**
     * Loads all menus with their items so MenuSerializer::serialize can call
     * count() without firing one query per menu.
     *
     * @return list<MenuInterface>
     */
    public function findAllForIndex(): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.items', 'i')
            ->addSelect('i')
            ->orderBy('m.name', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }

    public function findByLocation(string $location): ?MenuInterface
    {
        return $this->findOneBy(['location' => $location]);
    }

    /**
     * Loads all menus with items, children and translations in one query.
     * Keyed by location for O(1) lookup.
     *
     * @return array<string, MenuInterface>
     */
    public function findAllWithItemsKeyedByLocation(): array
    {
        $menus = $this->createQueryBuilder('m')
            ->leftJoin('m.items', 'i')->addSelect('i')
            ->leftJoin('i.translations', 't')->addSelect('t')
            ->leftJoin('i.children', 'c')->addSelect('c')
            ->leftJoin('c.translations', 'ct')->addSelect('ct')
            ->orderBy('m.location', Order::Ascending->value)
            ->addOrderBy('i.position', Order::Ascending->value)
            ->addOrderBy('c.position', Order::Ascending->value)
            ->getQuery()
            ->getResult();

        $indexed = [];
        foreach ($menus as $menu) {
            $indexed[$menu->getLocation()] = $menu;
        }

        return $indexed;
    }
}
