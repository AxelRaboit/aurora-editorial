<?php

declare(strict_types=1);

namespace App\Module\Editorial\Post\Entity;

use App\Core\User\Entity\User;
use App\Module\Editorial\Post\Enum\PostStatusEnum;
use App\Module\Editorial\Post\Repository\PostRevisionRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Contract\Entity\TimestampableInterface;
use Knp\DoctrineBehaviors\Model\Timestampable\TimestampableTrait;

#[ORM\Entity(repositoryClass: PostRevisionRepository::class)]
#[ORM\Table(name: 'post_revisions')]
#[ORM\Index(name: 'IDX_post_revision_post_created', columns: ['post_id', 'created_at'])]
class PostRevision implements TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: 'revisions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Post $post;

    #[ORM\Column]
    private int $postVersion;

    #[ORM\Column(length: 50, enumType: PostStatusEnum::class)]
    private PostStatusEnum $status;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $snapshot = [];

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $author = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPost(): Post
    {
        return $this->post;
    }

    public function setPost(Post $post): static
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

    /** @return array<string, mixed> */
    public function getSnapshot(): array
    {
        return $this->snapshot;
    }

    /** @param array<string, mixed> $snapshot */
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
