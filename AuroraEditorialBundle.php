<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial;

use Aurora\Core\Bundle\AbstractAuroraModuleBundle;
use Aurora\Module\Editorial\Comment\Entity\Comment;
use Aurora\Module\Editorial\Comment\Entity\CommentInterface;
use Aurora\Module\Editorial\Comment\Entity\CommentReaction;
use Aurora\Module\Editorial\Comment\Entity\CommentReactionInterface;
use Aurora\Module\Editorial\Form\Entity\Form;
use Aurora\Module\Editorial\Form\Entity\FormField;
use Aurora\Module\Editorial\Form\Entity\FormFieldInterface;
use Aurora\Module\Editorial\Form\Entity\FormFieldTranslation;
use Aurora\Module\Editorial\Form\Entity\FormFieldTranslationInterface;
use Aurora\Module\Editorial\Form\Entity\FormInterface;
use Aurora\Module\Editorial\Form\Entity\FormSubmission;
use Aurora\Module\Editorial\Form\Entity\FormSubmissionInterface;
use Aurora\Module\Editorial\Form\Entity\FormTranslation;
use Aurora\Module\Editorial\Form\Entity\FormTranslationInterface;
use Aurora\Module\Editorial\Menu\Entity\Menu;
use Aurora\Module\Editorial\Menu\Entity\MenuInterface;
use Aurora\Module\Editorial\Menu\Entity\MenuItem;
use Aurora\Module\Editorial\Menu\Entity\MenuItemInterface;
use Aurora\Module\Editorial\Menu\Entity\MenuItemTranslation;
use Aurora\Module\Editorial\Menu\Entity\MenuItemTranslationInterface;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostInterface;
use Aurora\Module\Editorial\Post\Entity\PostRevision;
use Aurora\Module\Editorial\Post\Entity\PostRevisionInterface;
use Aurora\Module\Editorial\Post\Entity\PostSlugHistory;
use Aurora\Module\Editorial\Post\Entity\PostSlugHistoryInterface;
use Aurora\Module\Editorial\Post\Entity\PostTranslation;
use Aurora\Module\Editorial\Post\Entity\PostTranslationInterface;
use Aurora\Module\Editorial\PostType\Entity\PostType;
use Aurora\Module\Editorial\PostType\Entity\PostTypeField;
use Aurora\Module\Editorial\PostType\Entity\PostTypeFieldInterface;
use Aurora\Module\Editorial\PostType\Entity\PostTypeInterface;
use Aurora\Module\Editorial\Taxonomy\Entity\Taxonomy;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyInterface;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTerm;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermInterface;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermTranslation;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermTranslationInterface;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTranslation;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTranslationInterface;

/** Self-contained bundle for the Editorial module. @see AbstractAuroraModuleBundle */
final class AuroraEditorialBundle extends AbstractAuroraModuleBundle
{
    protected function moduleName(): string
    {
        return 'Editorial';
    }

    protected function resolveTargetEntities(): array
    {
        return [
            MenuInterface::class => Menu::class,
            MenuItemInterface::class => MenuItem::class,
            MenuItemTranslationInterface::class => MenuItemTranslation::class,
            CommentInterface::class => Comment::class,
            CommentReactionInterface::class => CommentReaction::class,
            FormInterface::class => Form::class,
            FormFieldInterface::class => FormField::class,
            FormFieldTranslationInterface::class => FormFieldTranslation::class,
            FormSubmissionInterface::class => FormSubmission::class,
            FormTranslationInterface::class => FormTranslation::class,
            PostInterface::class => Post::class,
            PostRevisionInterface::class => PostRevision::class,
            PostSlugHistoryInterface::class => PostSlugHistory::class,
            PostTranslationInterface::class => PostTranslation::class,
            PostTypeInterface::class => PostType::class,
            PostTypeFieldInterface::class => PostTypeField::class,
            TaxonomyInterface::class => Taxonomy::class,
            TaxonomyTermInterface::class => TaxonomyTerm::class,
            TaxonomyTermTranslationInterface::class => TaxonomyTermTranslation::class,
            TaxonomyTranslationInterface::class => TaxonomyTranslation::class,
        ];
    }
}
