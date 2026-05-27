<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\PostType\Serializer;

use Aurora\Module\Editorial\PostType\Entity\PostTypeFieldInterface;
use Aurora\Module\Editorial\PostType\Entity\PostTypeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PostTypeSerializerInterface::class)]
class PostTypeSerializer implements PostTypeSerializerInterface
{
    public function serialize(PostTypeInterface $postType): array
    {
        $fields = array_map(
            static fn (PostTypeFieldInterface $field): array => [
                'id' => $field->getId(),
                'name' => $field->getName(),
                'label' => $field->getLabel(),
                'type' => $field->getType(),
                'required' => $field->isRequired(),
                'translatable' => $field->isTranslatable(),
                'options' => $field->getOptions(),
                'position' => $field->getPosition(),
            ],
            $postType->getFields()->toArray(),
        );

        return [
            'id' => $postType->getId(),
            'label' => $postType->getLabel(),
            'slug' => $postType->getSlug(),
            'icon' => $postType->getIcon(),
            'hasArchive' => $postType->hasArchive(),
            'isBuiltIn' => $postType->isBuiltIn(),
            'supports' => $postType->getSupports(),
            'taxonomyIds' => $postType->getTaxonomies()->map(fn ($tx): ?int => $tx->getId())->toArray(),
            'fields' => $fields,
        ];
    }
}
