<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\Editorial\Menu\Enum\MenuItemTargetTypeEnum;
use Aurora\Module\Editorial\Menu\Enum\MenuItemVisibilityEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractMenuItem implements MenuItemInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    protected ?string $reference = null;

    #[ORM\Column(length: 30, enumType: MenuItemTargetTypeEnum::class)]
    protected MenuItemTargetTypeEnum $targetType = MenuItemTargetTypeEnum::CustomUrl;

    /**
     * Soft FK: ID of the target post / term / post_type. No physical FK
     * so we don't cascade-delete the menu item if the target disappears —
     * the renderer handles missing targets gracefully.
     */
    #[ORM\Column(nullable: true)]
    protected ?int $targetId = null;

    #[ORM\Column(length: 1000, nullable: true)]
    protected ?string $customUrl = null;

    #[ORM\Column]
    protected bool $openInNewTab = false;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $cssClass = null;

    #[ORM\Column(length: 30, enumType: MenuItemVisibilityEnum::class)]
    protected MenuItemVisibilityEnum $visibility = MenuItemVisibilityEnum::Always;

    #[ORM\Column]
    protected int $position = 0;

    #[ORM\ManyToOne(targetEntity: MenuItemInterface::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    protected ?MenuItemInterface $parent = null;

    /** @var Collection<int, MenuItemInterface> */
    #[ORM\OneToMany(targetEntity: MenuItemInterface::class, mappedBy: 'parent', cascade: ['remove'])]
    #[ORM\OrderBy(['position' => Order::Ascending->value])]
    protected Collection $children;

    #[ORM\ManyToOne(targetEntity: MenuInterface::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected MenuInterface $menu;

    /** @var Collection<string, MenuItemTranslationInterface> */
    #[ORM\OneToMany(targetEntity: MenuItemTranslationInterface::class, mappedBy: 'menuItem', cascade: ['persist', 'remove'], orphanRemoval: true, indexBy: 'locale')]
    protected Collection $translations;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getTargetType(): MenuItemTargetTypeEnum
    {
        return $this->targetType;
    }

    public function setTargetType(MenuItemTargetTypeEnum $targetType): static
    {
        $this->targetType = $targetType;

        return $this;
    }

    public function getTargetId(): ?int
    {
        return $this->targetId;
    }

    public function setTargetId(?int $targetId): static
    {
        $this->targetId = $targetId;

        return $this;
    }

    public function getCustomUrl(): ?string
    {
        return $this->customUrl;
    }

    public function setCustomUrl(?string $customUrl): static
    {
        $this->customUrl = $customUrl;

        return $this;
    }

    public function isOpenInNewTab(): bool
    {
        return $this->openInNewTab;
    }

    public function setOpenInNewTab(bool $openInNewTab): static
    {
        $this->openInNewTab = $openInNewTab;

        return $this;
    }

    public function getCssClass(): ?string
    {
        return $this->cssClass;
    }

    public function setCssClass(?string $cssClass): static
    {
        $this->cssClass = $cssClass;

        return $this;
    }

    public function getVisibility(): MenuItemVisibilityEnum
    {
        return $this->visibility;
    }

    public function setVisibility(MenuItemVisibilityEnum $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getParent(): ?MenuItemInterface
    {
        return $this->parent;
    }

    public function setParent(?MenuItemInterface $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function getMenu(): MenuInterface
    {
        return $this->menu;
    }

    public function setMenu(MenuInterface $menu): static
    {
        $this->menu = $menu;

        return $this;
    }

    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function getTranslation(string $locale): ?MenuItemTranslationInterface
    {
        return $this->translations->get($locale);
    }

    public function addTranslation(MenuItemTranslationInterface $translation): static
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->set($translation->getLocale(), $translation);
            $translation->setMenuItem($this);
        }

        return $this;
    }

    public function removeTranslation(MenuItemTranslationInterface $translation): static
    {
        $this->translations->removeElement($translation);

        return $this;
    }
}
