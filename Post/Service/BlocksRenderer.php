<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Service;

use Aurora\Core\Support\Num;
use Aurora\Module\Ecommerce\EcommerceContext;
use Aurora\Module\Ecommerce\Listing\Entity\ListingInterface;
use Aurora\Module\Ecommerce\Listing\Repository\ListingRepository;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostTranslation;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Media\Library\Entity\Media;
use Aurora\Module\Media\Library\Service\MediaUrlGenerator;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Render Editor.js blocks to HTML for public front-end output.
 * Mirrors the minimal behaviour of the admin-side blocksRenderer.js but
 * runs server-side for SEO-friendly static HTML.
 */
final readonly class BlocksRenderer
{
    public function __construct(
        private PostRepository $postRepository,
        private PostTypeRepository $postTypeRepository,
        private UrlGeneratorInterface $urlGenerator,
        private RequestStack $requestStack,
        private ListingRepository $listingRepository,
        private EcommerceContext $ecommerceContext,
        private MediaUrlGenerator $mediaUrlGenerator,
    ) {}

    /**
     * @param array<int, array<string, mixed>> $blocks
     */
    public function render(array $blocks, string $locale): string
    {
        $output = '';
        foreach ($blocks as $block) {
            $output .= $this->renderBlock($block, $locale);
        }

        return $output;
    }

    private function renderBlock(array $block, string $locale): string
    {
        $type = (string) ($block['type'] ?? '');
        $data = is_array($block['data'] ?? null) ? $block['data'] : [];

        return match ($type) {
            'header', 'heading' => $this->renderHeader($data),
            'paragraph' => $this->renderParagraph($data),
            'list' => $this->renderList($data),
            'checklist' => $this->renderChecklist($data),
            'quote' => $this->renderQuote($data),
            'code' => $this->renderCode($data),
            'delimiter' => '<hr class="my-8 border-line">',
            'image' => $this->renderImage($data),
            'embed' => $this->renderEmbed($data),
            'table' => $this->renderTable($data),
            'callout' => $this->renderCallout($data),
            'twoColumn' => $this->renderTwoColumn($data, $locale),
            'mediaText' => $this->renderMediaText($data),
            'postsList' => $this->renderPostsList($data, $locale),
            'productGrid' => $this->renderProductGrid($data, $locale),
            default => '',
        };
    }

    /**
     * Renders a grid of Ecommerce listings inline within a post.
     *
     * Block data shape:
     *   {
     *     listingIds: int[],
     *     columns: 1..4,
     *     title?: string,
     *   }
     */
    private function renderProductGrid(array $data, string $locale): string
    {
        if (!$this->ecommerceContext->isFrontEnabled()) {
            return '';
        }

        $listingIds = array_values(array_filter(
            array_map(intval(...), (array) ($data['listingIds'] ?? [])),
            static fn (int $id): bool => $id > 0,
        ));
        $columns = Num::clamp((int) ($data['columns'] ?? 3), 1, 4);
        $title = $this->safeHtml($data['title'] ?? '');

        if ([] === $listingIds) {
            return '' !== $title ? sprintf('<section class="product-grid my-8"><h2 class="text-2xl font-bold mb-4">%s</h2></section>', $title) : '';
        }

        $found = $this->listingRepository->findBy(['id' => $listingIds]);
        $byId = [];
        foreach ($found as $listing) {
            if ($listing->isVisibleOnShop()) {
                $byId[$listing->getId()] = $listing;
            }
        }

        $items = [];
        foreach ($listingIds as $id) {
            if (isset($byId[$id])) {
                $items[] = $byId[$id];
            }
        }

        if ([] === $items) {
            return '' !== $title ? sprintf('<section class="product-grid my-8"><h2 class="text-2xl font-bold mb-4">%s</h2></section>', $title) : '';
        }

        $gridClass = match ($columns) {
            1 => 'grid-cols-1',
            2 => 'grid-cols-1 sm:grid-cols-2',
            3 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
            default => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4',
        };

        $cards = '';
        foreach ($items as $listing) {
            $cards .= $this->renderListingCard($listing, $locale);
        }

        return sprintf(
            '<section class="product-grid my-8">%s<div class="grid %s gap-4">%s</div></section>',
            '' !== $title ? sprintf('<h2 class="text-2xl font-bold mb-4">%s</h2>', $title) : '',
            $gridClass,
            $cards,
        );
    }

    private function renderListingCard(ListingInterface $listing, string $locale): string
    {
        $url = $this->urlGenerator->generate('frontend_shop_product', ['locale' => $locale, 'slug' => $listing->getSlug()]);
        $title = htmlspecialchars($listing->getDisplayTitle(), ENT_QUOTES, 'UTF-8');
        $product = $listing->getProduct();
        $priceCents = $product->getPriceCents();
        $price = '';
        if (null !== $priceCents) {
            $amount = $priceCents / (10 ** $product->getCurrency()->decimals());
            $price = sprintf('<p class="text-base font-bold text-accent">%s %s</p>', number_format($amount, 2, ',', ' '), htmlspecialchars($product->getCurrency()->symbol(), ENT_QUOTES, 'UTF-8'));
        }

        $imageHtml = '';
        $featured = $listing->getFeaturedImage() ?? $listing->getProduct()->getImage();
        if ($featured instanceof Media) {
            $src = htmlspecialchars((string) ($this->mediaUrlGenerator->variantUrl($featured, 'medium') ?? $this->mediaUrlGenerator->publicUrl($featured)), ENT_QUOTES, 'UTF-8');
            $alt = htmlspecialchars($featured->getAlt() ?? $listing->getDisplayTitle(), ENT_QUOTES, 'UTF-8');
            $imageHtml = sprintf('<div class="aspect-square bg-surface-2 overflow-hidden"><img src="%s" alt="%s" class="w-full h-full object-cover" loading="lazy"></div>', $src, $alt);
        }

        return sprintf(
            '<article class="product-card bg-surface border border-line/60 rounded-xl overflow-hidden hover:border-accent transition-colors"><a href="%s" class="block">%s<div class="p-4 space-y-2"><h3 class="text-lg font-semibold text-primary">%s</h3>%s</div></a></article>',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            $imageHtml,
            $title,
            $price,
        );
    }

    private function renderHeader(array $data): string
    {
        $level = (int) ($data['level'] ?? 2);
        $level = Num::clamp($level, 1, 6);

        $text = $this->safeHtml($data['text'] ?? '');

        return sprintf('<h%d>%s</h%d>', $level, $text, $level);
    }

    private function renderParagraph(array $data): string
    {
        return sprintf('<p>%s</p>', $this->safeHtml($data['text'] ?? ''));
    }

    private function renderList(array $data): string
    {
        $style = 'ordered' === ($data['style'] ?? 'unordered') ? 'ol' : 'ul';
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        $itemsHtml = '';
        foreach ($items as $item) {
            if (is_string($item)) {
                $itemsHtml .= sprintf('<li>%s</li>', $this->safeHtml($item));
            } elseif (is_array($item) && isset($item['content'])) {
                $itemsHtml .= sprintf('<li>%s</li>', $this->safeHtml((string) $item['content']));
            }
        }

        return sprintf('<%s>%s</%s>', $style, $itemsHtml, $style);
    }

    private function renderChecklist(array $data): string
    {
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        $html = '<ul class="checklist">';
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $text = $this->safeHtml($item['text'] ?? '');
            $checked = ($item['checked'] ?? false) ? 'checked' : '';
            $html .= sprintf('<li><input type="checkbox" disabled %s> %s</li>', $checked, $text);
        }

        return $html.'</ul>';
    }

    private function renderQuote(array $data): string
    {
        $text = $this->safeHtml($data['text'] ?? '');
        $caption = $this->safeHtml($data['caption'] ?? '');

        return sprintf(
            '<blockquote><p>%s</p>%s</blockquote>',
            $text,
            '' !== $caption ? sprintf('<cite>%s</cite>', $caption) : '',
        );
    }

    private function renderCode(array $data): string
    {
        $code = htmlspecialchars((string) ($data['code'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return sprintf('<pre><code>%s</code></pre>', $code);
    }

    private function renderImage(array $data): string
    {
        $file = is_array($data['file'] ?? null) ? $data['file'] : [];
        $url = htmlspecialchars((string) ($file['url'] ?? ''), ENT_QUOTES, 'UTF-8');
        if ('' === $url) {
            return '';
        }

        $alt = htmlspecialchars((string) ($data['caption'] ?? ''), ENT_QUOTES, 'UTF-8');
        $caption = $this->safeHtml($data['caption'] ?? '');

        return sprintf(
            '<figure><img src="%s" alt="%s" loading="lazy">%s</figure>',
            $url,
            $alt,
            '' !== $caption ? sprintf('<figcaption>%s</figcaption>', $caption) : '',
        );
    }

    private function renderEmbed(array $data): string
    {
        $embedUrl = htmlspecialchars((string) ($data['embed'] ?? ''), ENT_QUOTES, 'UTF-8');
        if ('' === $embedUrl) {
            return '';
        }

        return sprintf(
            '<div class="embed"><iframe src="%s" frameborder="0" allowfullscreen loading="lazy"></iframe></div>',
            $embedUrl,
        );
    }

    private function renderTable(array $data): string
    {
        $rows = is_array($data['content'] ?? null) ? $data['content'] : [];
        $withHeadings = (bool) ($data['withHeadings'] ?? false);
        $html = '<table>';
        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $tag = ($withHeadings && 0 === $index) ? 'th' : 'td';
            $cells = '';
            foreach ($row as $cell) {
                $cells .= sprintf('<%s>%s</%s>', $tag, $this->safeHtml((string) $cell), $tag);
            }

            $html .= '<tr>'.$cells.'</tr>';
        }

        return $html.'</table>';
    }

    private function renderCallout(array $data): string
    {
        $type = (string) ($data['type'] ?? 'info');
        $text = $this->safeHtml($data['text'] ?? '');

        return sprintf('<aside class="callout callout-%s">%s</aside>', htmlspecialchars($type, ENT_QUOTES, 'UTF-8'), $text);
    }

    private function renderTwoColumn(array $data, string $locale): string
    {
        $left = is_array($data['left'] ?? null) ? $this->render($data['left'], $locale) : '';
        $right = is_array($data['right'] ?? null) ? $this->render($data['right'], $locale) : '';

        return sprintf('<div class="two-column"><div>%s</div><div>%s</div></div>', $left, $right);
    }

    private function renderMediaText(array $data): string
    {
        $image = is_array($data['image'] ?? null) ? $data['image'] : [];
        $url = htmlspecialchars((string) ($image['url'] ?? ''), ENT_QUOTES, 'UTF-8');
        $text = $this->safeHtml($data['text'] ?? '');

        return sprintf(
            '<div class="media-text">%s<div>%s</div></div>',
            '' !== $url ? sprintf('<figure><img src="%s" alt="" loading="lazy"></figure>', $url) : '',
            $text,
        );
    }

    /**
     * Renders a grid of posts inline within a post's content.
     *
     * Block data shape:
     *   {
     *     mode: "manual" | "auto",
     *     postTypeSlug?: string,
     *     postIds?: int[],          // when mode = manual
     *     perPage?: int,            // when mode = auto
     *     columns?: int (1..4),
     *     title?: string,
     *   }
     *
     * Backwards-compatible with legacy { limit } shape.
     */
    private function renderPostsList(array $data, string $locale): string
    {
        $mode = ('manual' === ($data['mode'] ?? null)) ? 'manual' : 'auto';
        $postTypeSlug = (string) ($data['postTypeSlug'] ?? 'article');
        $columns = Num::clamp((int) ($data['columns'] ?? 3), 1, 4);
        $title = $this->safeHtml($data['title'] ?? '');

        $postType = $this->postTypeRepository->findOneBy(['slug' => $postTypeSlug]);
        if (null === $postType) {
            return '';
        }

        $items = [];
        $paginationHtml = '';

        if ('manual' === $mode) {
            $postIds = array_values(array_filter(
                array_map(intval(...), (array) ($data['postIds'] ?? [])),
                static fn (int $id): bool => $id > 0,
            ));
            if ([] === $postIds) {
                return '' !== $title ? sprintf('<section class="posts-list my-8"><h2 class="text-2xl font-bold mb-4">%s</h2></section>', $title) : '';
            }

            $found = $this->postRepository->findByIds($postIds);
            $byId = [];
            foreach ($found as $post) {
                if (PostStatusEnum::Published === $post->getStatus() && !$post->getDeletedAt() instanceof DateTimeImmutable) {
                    $byId[$post->getId()] = $post;
                }
            }

            // preserve admin-defined order
            foreach ($postIds as $id) {
                if (isset($byId[$id])) {
                    $items[] = $byId[$id];
                }
            }
        } else {
            $perPage = Num::clamp((int) ($data['perPage'] ?? $data['limit'] ?? 12), 1, 100);
            $page = max(1, (int) ($this->requestStack->getCurrentRequest()?->query->get('page') ?? 1));
            $result = $this->postRepository->findPublishedByPostTypeWithSearch($postType->getId(), $page, $perPage, $locale);
            $items = $result['items'];
            $totalPages = (int) $result['totalPages'];
            if ($totalPages > 1) {
                $paginationHtml = $this->renderPagination($page, $totalPages);
            }
        }

        if ([] === $items) {
            return '' !== $title ? sprintf('<section class="posts-list my-8"><h2 class="text-2xl font-bold mb-4">%s</h2></section>', $title) : '';
        }

        $gridClass = match ($columns) {
            1 => 'grid-cols-1',
            2 => 'grid-cols-1 sm:grid-cols-2',
            3 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
            default => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4',
        };

        $cards = '';
        foreach ($items as $post) {
            $cards .= $this->renderPostCard($post, $locale);
        }

        return sprintf(
            '<section class="posts-list my-8">%s<div class="grid %s gap-4">%s</div>%s</section>',
            '' !== $title ? sprintf('<h2 class="text-2xl font-bold mb-4">%s</h2>', $title) : '',
            $gridClass,
            $cards,
            $paginationHtml,
        );
    }

    private function renderPagination(int $page, int $totalPages): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $basePath = $request?->getPathInfo() ?? '';
        $buildUrl = (static fn (int $targetPage): string => 1 === $targetPage ? $basePath : $basePath.'?page='.$targetPage);

        $prev = $page > 1
            ? sprintf('<a href="%s" class="px-3 py-1.5 rounded border border-line text-sm hover:bg-surface-2">&larr;</a>', htmlspecialchars($buildUrl($page - 1), ENT_QUOTES, 'UTF-8'))
            : '<span class="px-3 py-1.5 rounded border border-line text-sm text-muted opacity-50">&larr;</span>';

        $next = $page < $totalPages
            ? sprintf('<a href="%s" class="px-3 py-1.5 rounded border border-line text-sm hover:bg-surface-2">&rarr;</a>', htmlspecialchars($buildUrl($page + 1), ENT_QUOTES, 'UTF-8'))
            : '<span class="px-3 py-1.5 rounded border border-line text-sm text-muted opacity-50">&rarr;</span>';

        return sprintf(
            '<nav class="mt-6 flex items-center justify-center gap-2"><span class="text-sm text-muted">%d / %d</span>%s%s</nav>',
            $page,
            $totalPages,
            $prev,
            $next,
        );
    }

    private function renderPostCard(Post $post, string $locale): string
    {
        $translation = $post->getTranslation($locale) ?? $post->getTranslations()->first();
        if (!$translation instanceof PostTranslation) {
            return '';
        }

        $slug = $translation->getSlug();
        if (null === $slug || '' === $slug) {
            return '';
        }

        $url = $this->urlGenerator->generate('editorial_post', [
            'locale' => $locale,
            'postTypeSlug' => $post->getPostType()->getSlug(),
            'slug' => $slug,
        ]);

        $title = htmlspecialchars($translation->getTitle() ?? '—', ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars($translation->getMetaDescription() ?? '', ENT_QUOTES, 'UTF-8');

        $featured = $post->getFeaturedMedia();
        $imageHtml = '';
        if ($featured instanceof Media) {
            $src = htmlspecialchars((string) ($this->mediaUrlGenerator->variantUrl($featured, 'medium') ?? $this->mediaUrlGenerator->publicUrl($featured)), ENT_QUOTES, 'UTF-8');
            $alt = htmlspecialchars($featured->getAlt() ?? $title, ENT_QUOTES, 'UTF-8');
            $imageHtml = sprintf(
                '<div class="aspect-[16/9] bg-surface-2 overflow-hidden"><img src="%s" alt="%s" class="w-full h-full object-cover" loading="lazy"></div>',
                $src,
                $alt,
            );
        }

        $dateHtml = '';
        if ($post->getPublishedAt() instanceof DateTimeImmutable) {
            $iso = $post->getPublishedAt()->format('c');
            $human = $post->getPublishedAt()->format('d/m/Y');
            $dateHtml = sprintf('<time datetime="%s" class="block text-xs text-muted">%s</time>', $iso, $human);
        }

        return sprintf(
            '<article class="post-card bg-surface border border-line/60 rounded-xl overflow-hidden">'
                .'<a href="%s" class="block">%s<div class="p-4 space-y-2">'
                .'<h3 class="text-lg font-semibold text-primary">%s</h3>'
                .'%s%s'
                .'</div></a></article>',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            $imageHtml,
            $title,
            '' !== $description ? sprintf('<p class="text-sm text-muted line-clamp-3">%s</p>', $description) : '',
            $dateHtml,
        );
    }

    /**
     * Editor.js lets users input light HTML (b, i, a, code, etc.) in text
     * fields. We allow a minimal safe subset and escape everything else.
     */
    private function safeHtml(mixed $value): string
    {
        if (!is_string($value)) {
            return '';
        }

        // Remove script/style tags entirely
        $value = preg_replace('#<(script|style|iframe)[^>]*>.*?</\1>#is', '', $value) ?? '';

        // Strip all tags except a short whitelist
        $allowed = '<a><b><strong><i><em><u><s><br><code><mark>';

        return strip_tags($value, $allowed);
    }
}
