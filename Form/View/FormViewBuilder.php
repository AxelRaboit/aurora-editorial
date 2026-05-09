<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\View;

use Aurora\Core\Frontend\Service\Context;
use Aurora\Core\Theme\Service\ThemeContext;
use Aurora\Module\Editorial\Form\Entity\FormTranslationInterface;
use Aurora\Module\Editorial\Form\Serializer\FormSerializerInterface;

/**
 * Builds the Twig payloads consumed by the public form views.
 */
final readonly class FormViewBuilder
{
    public function __construct(
        private FormSerializerInterface $formSerializer,
        private Context $context,
        private ThemeContext $themeContext,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function showView(FormTranslationInterface $translation, string $locale): array
    {
        $form = $translation->getForm();
        $fields = array_values(array_map(
            fn ($field): array => $this->formSerializer->serializeFieldForLocale($field, $locale),
            $form->getFields()->toArray(),
        ));

        return [
            'locale' => $locale,
            'context' => $this->context,
            'showFrontMenus' => true,
            'themeContext' => $this->themeContext,
            'form' => $form,
            'translation' => $translation,
            'fields' => $fields,
        ];
    }
}
