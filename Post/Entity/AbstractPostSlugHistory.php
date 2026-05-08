<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Contract\Entity\TimestampableInterface;
use Knp\DoctrineBehaviors\Model\Timestampable\TimestampableTrait;

#[ORM\MappedSuperclass]
abstract class AbstractPostSlugHistory implements PostSlugHistoryInterface, TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: PostInterface::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected PostInterface $post;

    #[ORM\Column(length: 10)]
    protected string $locale;

    #[ORM\Column(length: 255)]
    protected string $slug;

    public function getPost(): PostInterface
    {
        return $this->post;
    }

    public function setPost(PostInterface $post): static
    {
        $this->post = $post;

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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }
}
