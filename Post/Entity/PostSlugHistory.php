<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Entity;

use Aurora\Module\Editorial\Post\Repository\PostSlugHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PostSlugHistoryRepository::class)]
#[ORM\Table(name: 'core_post_slug_history')]
#[ORM\UniqueConstraint(name: 'UNIQ_post_slug_history_locale_slug', columns: ['locale', 'slug'])]
#[ORM\Index(name: 'IDX_post_slug_history_post', columns: ['post_id'])]
class PostSlugHistory extends AbstractPostSlugHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_post_slug_history_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
