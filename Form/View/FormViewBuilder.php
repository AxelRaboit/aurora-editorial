<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\View;

use Aurora\Core\Frontend\Service\FrontContext;
use Aurora\Core\Theme\Service\ThemeContext;
use Aurora\Module\Editorial\Form\Entity\FormTranslation;
use Aurora\Module\Editorial\Form\Serializer\FormSerializer;

/**
 * Builds the Twig payloads consumed by the public form views.
 */
final readonly class FormViewBuilder
{
    public function __construct(
        private FormSerializer $formSerializer,
        private FrontContext $frontContext,
        private ThemeContext $themeContext,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function showView(FormTranslation $translation, string $locale): array
    {
        $form = $translation->getForm();
        $fields = array_values(array_map(
            fn ($field): array => $this->formSerializer->serializeFieldForLocale($field, $locale),
            $form->getFields()->toArray(),
        ));

        return [
            'locale' => $locale,
            'context' => $this->frontContext,
            'showFrontMenus' => true,
            'themeContext' => $this->themeContext,
            'form' => $form,
            'translation' => $translation,
            'fields' => $fields,
        ];
    }
}
