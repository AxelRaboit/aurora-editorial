<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Comment\Entity;

use Aurora\Module\Editorial\Comment\Enum\ReactionTypeEnum;
use Aurora\Module\Editorial\Comment\Repository\CommentReactionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentReactionRepository::class)]
#[ORM\Table(name: 'core_comment_reactions')]
#[ORM\UniqueConstraint(name: 'uniq_comment_fingerprint', columns: ['comment_id', 'fingerprint'])]
class CommentReaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_comment_reaction_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Comment::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Comment $comment;

    #[ORM\Column(length: 20, enumType: ReactionTypeEnum::class)]
    private ReactionTypeEnum $type;

    #[ORM\Column(length: 64, nullable: false)]
    private string $fingerprint;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getComment(): Comment
    {
        return $this->comment;
    }

    public function setComment(Comment $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getType(): ReactionTypeEnum
    {
        return $this->type;
    }

    public function setType(ReactionTypeEnum $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getFingerprint(): string
    {
        return $this->fingerprint;
    }

    public function setFingerprint(string $fingerprint): static
    {
        $this->fingerprint = $fingerprint;

        return $this;
    }
}
