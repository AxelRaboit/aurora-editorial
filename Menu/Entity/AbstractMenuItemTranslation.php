<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractMenuItemTranslation implements MenuItemTranslationInterface
{
    #[ORM\ManyToOne(targetEntity: MenuItemInterface::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected MenuItemInterface $menuItem;

    #[ORM\Column(length: 10)]
    protected string $locale;

    /**
     * Optional override for the auto-resolved label (post title, term name…).
     * Null means: use the target's own label as displayed text.
     */
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $label = null;

    public function getMenuItem(): MenuItemInterface
    {
        return $this->menuItem;
    }

    public function setMenuItem(MenuItemInterface $menuItem): static
    {
        $this->menuItem = $menuItem;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }
}
