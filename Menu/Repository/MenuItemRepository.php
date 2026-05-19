<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Editorial\Menu\Entity\Menu;
use Aurora\Module\Editorial\Menu\Entity\MenuItem;
use Aurora\Module\Editorial\Menu\Entity\MenuItemInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<MenuItemInterface>
 */
class MenuItemRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MenuItem::class, MenuItemInterface::class);
    }

    /**
     * Return root items (parent = null) of a menu, ordered by position,
     * with children + translations eager-loaded.
     *
     * @return MenuItem[]
     */
    public function findRootItems(Menu $menu): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.children', 'c')->addSelect('c')
            ->leftJoin('i.translations', 't')->addSelect('t')
            ->leftJoin('c.translations', 'ct')->addSelect('ct')
            ->where('i.menu = :menu')
            ->andWhere('i.parent IS NULL')
            ->setParameter('menu', $menu)
            ->orderBy('i.position', Order::Ascending->value)
            ->addOrderBy('c.position', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}
