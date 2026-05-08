<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Entity;

use Aurora\Core\User\Entity\User;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Contract\Entity\TimestampableInterface;
use Knp\DoctrineBehaviors\Model\Timestampable\TimestampableTrait;

#[ORM\MappedSuperclass]
abstract class AbstractPostRevision implements PostRevisionInterface, TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: PostInterface::class, inversedBy: 'revisions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected PostInterface $post;

    #[ORM\Column]
    protected int $postVersion;

    #[ORM\Column(length: 50, enumType: PostStatusEnum::class)]
    protected PostStatusEnum $status;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    protected array $snapshot = [];

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?User $author = null;

    public function getPost(): PostInterface
    {
        return $this->post;
    }

    public function setPost(PostInterface $post): static
    {
        $this->post = $post;

        return $this;
    }

    public function getPostVersion(): int
    {
        return $this->postVersion;
    }

    public function setPostVersion(int $postVersion): static
    {
        $this->postVersion = $postVersion;

        return $this;
    }

    public function getStatus(): PostStatusEnum
    {
        return $this->status;
    }

    public function setStatus(PostStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSnapshot(): array
    {
        return $this->snapshot;
    }

    public function setSnapshot(array $snapshot): static
    {
        $this->snapshot = $snapshot;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getCreatedAtImmutable(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromInterface($this->getCreatedAt());
    }
}
