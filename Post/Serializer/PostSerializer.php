<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Serializer;

use Aurora\Core\Locale\Service\LocaleContextInterface;
use Aurora\Module\Editorial\Post\Entity\PostInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Aurora\Core\Media\Service\MediaUrlGenerator;

#[AsAlias(PostSerializerInterface::class)]
class PostSerializer implements PostSerializerInterface
{
    public function __construct(
        protected readonly LocaleContextInterface $localeContext,
        protected readonly MediaUrlGenerator $mediaUrlGenerator,
    ) {}

    /**
     * Compact projection used by reference pickers (post link block, related posts…).
     * Falls back to the first available translation when the default locale has none.
     */
    public function serializeReference(PostInterface $post): array
    {
        return [
            'id' => $post->getId(),
            'title' => $this->preferredTitle($post),
            'status' => $post->getStatus()->value,
            'postTypeId' => $post->getPostType()->getId(),
            'postType' => $post->getPostType()->getLabel(),
        ];
    }

    private function preferredTitle(PostInterface $post): ?string
    {
        $defaultTranslation = $post->getTranslation($this->localeContext->getDefaultLocale());

        return $defaultTranslation?->getTitle() ?? ($post->getTranslations()->first() ?: null)?->getTitle();
    }

    public function serialize(PostInterface $post): array
    {
        $defaultTranslation = $post->getTranslation($this->localeContext->getDefaultLocale());

        return [
            'id' => $post->getId(),
            'version' => $post->getVersion(),
            'status' => $post->getStatus()->value,
            'postType' => [
                'id' => $post->getPostType()->getId(),
                'label' => $post->getPostType()->getLabel(),
                'slug' => $post->getPostType()->getSlug(),
            ],
            'title' => $defaultTranslation?->getTitle(),
            'slug' => $defaultTranslation?->getSlug(),
            'termIds' => $post->getTerms()->map(fn (object $term): ?int => $term->getId())->toArray(),
            'relatedPostIds' => $post->getRelatedPosts()->map(fn (PostInterface $related): ?int => $related->getId())->toArray(),
            'publishedAt' => $post->getPublishedAt()?->format(DateTimeInterface::ATOM),
            'scheduledAt' => $post->getScheduledAt()?->format(DateTimeInterface::ATOM),
            'deletedAt' => $post->getDeletedAt()?->format(DateTimeInterface::ATOM),
            'trashed' => $post->isTrashed(),
            'commentsEnabled' => $post->isCommentsEnabled(),
            'createdAt' => $post->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $post->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }

    public function serializeFull(PostInterface $post): array
    {
        $translations = [];
        foreach ($post->getTranslations() as $locale => $translation) {
            $translations[(string) $locale] = [
                'title' => $translation->getTitle(),
                'slug' => $translation->getSlug(),
                'blocks' => $translation->getBlocks(),
                'metaTitle' => $translation->getMetaTitle(),
                'metaDescription' => $translation->getMetaDescription(),
                'customFields' => $translation->getCustomFields(),
                'ogImageMediaId' => $translation->getOgImage()?->getId(),
                'ogImageUrl' => $this->mediaUrlGenerator->publicUrl($translation->getOgImage()),
                'ogImageFocalPosition' => $translation->getOgImage()?->getFocalPositionCss(),
                'canonicalUrl' => $translation->getCanonicalUrl(),
                'noindex' => $translation->isNoindex(),
                'focusKeyword' => $translation->getFocusKeyword(),
                'jsonLd' => $translation->getJsonLd(),
            ];
        }

        $relatedPosts = [];
        foreach ($post->getRelatedPosts() as $related) {
            $relatedPosts[] = [
                'id' => $related->getId(),
                'title' => $this->preferredTitle($related),
                'status' => $related->getStatus()->value,
                'postType' => $related->getPostType()->getLabel(),
            ];
        }

        return [
            ...$this->serialize($post),
            'featuredMediaId' => $post->getFeaturedMedia()?->getId(),
            'featuredMediaUrl' => $this->mediaUrlGenerator->publicUrl($post->getFeaturedMedia()),
            'featuredMediaFocalPosition' => $post->getFeaturedMedia()?->getFocalPositionCss(),
            'translations' => $translations,
            'relatedPosts' => $relatedPosts,
        ];
    }

    public function serializeCard(PostInterface $post, string $locale): array
    {
        $translation = $post->getTranslation($locale);
        $featured = $post->getFeaturedMedia();

        return [
            'id' => $post->getId(),
            'title' => $translation?->getTitle(),
            'slug' => $translation?->getSlug(),
            'metaDescription' => $translation?->getMetaDescription(),
            'publishedAt' => $post->getPublishedAt()?->format(DateTimeInterface::ATOM),
            'postTypeSlug' => $post->getPostType()->getSlug(),
            'featuredMediaUrl' => $this->mediaUrlGenerator->variantUrl($featured, 'medium') ?? $this->mediaUrlGenerator->publicUrl($featured),
            'featuredMediaFocalPosition' => $featured?->getFocalPositionCss(),
        ];
    }
}
