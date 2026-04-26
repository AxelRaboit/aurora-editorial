<?php

declare(strict_types=1);

namespace App\Module\Editorial\Post\Service;

use App\Module\Editorial\Post\Entity\PostTranslation;
use App\Module\Editorial\Post\Entity\PostTypeField;

final readonly class PostTextExtractor
{
    private const TEXT_FIELD_TYPES = ['text', 'textarea', 'url', 'email'];

    /**
     * Build the search_content string for a post translation by concatenating
     * every indexable piece of text: meta fields, block text, custom field values.
     */
    public function extract(PostTranslation $translation): string
    {
        $parts = [
            $translation->getMetaTitle(),
            $translation->getMetaDescription(),
            $translation->getFocusKeyword(),
            $this->textFromBlocks($translation->getBlocks()),
            $this->textFromCustomFields($translation),
        ];

        $joined = implode(' ', array_filter(
            array_map(static fn (?string $part): string => $part ?? '', $parts),
            static fn (string $part): bool => '' !== mb_trim($part),
        ));

        return mb_trim(preg_replace('/\s+/', ' ', $joined) ?? '');
    }

    /**
     * @param array<int, array<string, mixed>> $blocks
     */
    public function textFromBlocks(array $blocks): string
    {
        $collected = [];
        foreach ($blocks as $block) {
            $this->collectStrings($block, $collected);
        }

        return implode(' ', $collected);
    }

    private function textFromCustomFields(PostTranslation $translation): string
    {
        $fieldDefinitions = $this->postTypeFields($translation);
        $customFields = $translation->getCustomFields();
        $parts = [];

        foreach ($customFields as $name => $value) {
            $definition = $fieldDefinitions[$name] ?? null;
            if (null === $definition) {
                if (is_string($value)) {
                    $parts[] = $value;
                }

                continue;
            }

            $type = $definition->getType();
            if (in_array($type, self::TEXT_FIELD_TYPES, true) && is_string($value)) {
                $parts[] = $value;
            } elseif ('select' === $type && is_string($value)) {
                $choices = $definition->getOptions()['choices'] ?? [];
                foreach ($choices as $choice) {
                    if (is_array($choice) && ($choice['value'] ?? null) === $value) {
                        $parts[] = (string) ($choice['label'] ?? $value);
                        break;
                    }
                }
            }
        }

        return implode(' ', $parts);
    }

    /** @return array<string, PostTypeField> */
    private function postTypeFields(PostTranslation $translation): array
    {
        $map = [];
        foreach ($translation->getPost()->getPostType()->getFields() as $field) {
            $map[$field->getName()] = $field;
        }

        return $map;
    }

    /**
     * @param list<string> $output
     */
    private function collectStrings($value, array &$output): void
    {
        if (is_string($value)) {
            $plain = mb_trim(strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            if ('' !== $plain) {
                $output[] = $plain;
            }

            return;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                $this->collectStrings($item, $output);
            }
        }
    }
}
