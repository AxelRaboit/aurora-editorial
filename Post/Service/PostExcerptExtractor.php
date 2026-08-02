<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Service;

/**
 * The short text a listing shows under a post's title.
 *
 * Cards used to render the meta description. That text is written for a search
 * result and cut off around 160 characters, so using it as the visible teaser
 * meant one string had to serve two readers at once — and a page whose author
 * wrote for the visitor ended up with a 247-character "meta description".
 *
 * The teaser now comes from the content itself: the Introduction block if the
 * page has one, otherwise its first paragraph. Nothing to fill in twice, and
 * the meta description goes back to being SEO-only.
 */
final readonly class PostExcerptExtractor
{
    /**
     * Long enough for the three lines a card clamps to, short enough that a
     * listing does not ship whole pages of prose it will never show.
     */
    private const int MAX_LENGTH = 240;

    /**
     * @param array<int, array<string, mixed>> $blocks
     */
    public function fromBlocks(array $blocks): ?string
    {
        $text = $this->firstOfType($blocks, 'intro') ?? $this->firstOfType($blocks, 'paragraph');

        return null === $text ? null : $this->truncate($text);
    }

    /**
     * @param array<int, array<string, mixed>> $blocks
     */
    private function firstOfType(array $blocks, string $type): ?string
    {
        foreach ($blocks as $block) {
            if (($block['type'] ?? null) !== $type) {
                continue;
            }

            $data = $block['data'] ?? null;
            $text = is_array($data) ? ($data['text'] ?? null) : null;

            if (!is_string($text)) {
                continue;
            }

            $plain = $this->plainText($text);
            if ('' !== $plain) {
                return $plain;
            }
        }

        return null;
    }

    /**
     * Block text is the field's innerHTML, so it carries the inline toolbar's
     * markup. A card renders it as text, not HTML.
     */
    private function plainText(string $html): string
    {
        $decoded = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return mb_trim(preg_replace('/\s+/u', ' ', $decoded) ?? '');
    }

    private function truncate(string $text): string
    {
        if (mb_strlen($text) <= self::MAX_LENGTH) {
            return $text;
        }

        $cut = mb_substr($text, 0, self::MAX_LENGTH);
        $lastSpace = mb_strrpos($cut, ' ');

        if (false !== $lastSpace && $lastSpace > 0) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }

        return mb_rtrim($cut, " \t\n\r\0\x0B,;:.").'…';
    }
}
