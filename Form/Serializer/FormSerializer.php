<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Serializer;

use Aurora\Module\Editorial\Form\Entity\Form;
use Aurora\Module\Editorial\Form\Entity\FormField;
use Aurora\Module\Editorial\Form\Entity\FormFieldTranslation;
use Aurora\Module\Editorial\Form\Entity\FormSubmission;
use Symfony\Contracts\Translation\TranslatorInterface;

use const DATE_ATOM;

final readonly class FormSerializer
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function serialize(Form $form, bool $withFields = true): array
    {
        $result = [
            'id' => $form->getId(),
            'notifyEmail' => $form->getNotifyEmail(),
            'active' => $form->isActive(),
            'submissionCount' => $form->getSubmissions()->count(),
            'createdAt' => $form->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $form->getUpdatedAt()->format(DATE_ATOM),
            'translations' => $this->serializeFormTranslations($form),
        ];

        if ($withFields) {
            $result['fields'] = array_values(array_map(
                $this->serializeField(...),
                $form->getFields()->toArray(),
            ));
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeField(FormField $field): array
    {
        return [
            'id' => $field->getId(),
            'type' => $field->getType()->value,
            'typeLabel' => $this->translator->trans($field->getType()->getLabelKey()),
            'required' => $field->isRequired(),
            'position' => $field->getPosition(),
            'translations' => $this->serializeFieldTranslations($field),
        ];
    }

    /**
     * Serializes a field for front-end rendering in a specific locale.
     *
     * @return array<string, mixed>
     */
    public function serializeFieldForLocale(FormField $field, string $locale): array
    {
        $translation = $field->getTranslation($locale);
        if (!$translation instanceof FormFieldTranslation) {
            $first = $field->getTranslations()->first();
            $translation = $first instanceof FormFieldTranslation ? $first : null;
        }

        return [
            'id' => $field->getId(),
            'type' => $field->getType()->value,
            'typeLabel' => $this->translator->trans($field->getType()->getLabelKey()),
            'required' => $field->isRequired(),
            'label' => $translation?->getLabel() ?? '',
            'placeholder' => $translation?->getPlaceholder(),
            'options' => $translation?->getOptions() ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeSubmission(FormSubmission $submission): array
    {
        return [
            'id' => $submission->getId(),
            'submittedAt' => $submission->getSubmittedAt()->format(DATE_ATOM),
            'locale' => $submission->getLocale(),
            'ip' => $submission->getIp(),
            'data' => $submission->getData(),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function serializeFormTranslations(Form $form): array
    {
        $result = [];
        foreach ($form->getTranslations() as $translation) {
            $result[$translation->getLocale()] = [
                'title' => $translation->getTitle(),
                'slug' => $translation->getSlug(),
                'description' => $translation->getDescription(),
            ];
        }

        return $result;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function serializeFieldTranslations(FormField $field): array
    {
        $result = [];
        foreach ($field->getTranslations() as $translation) {
            $result[$translation->getLocale()] = [
                'label' => $translation->getLabel(),
                'placeholder' => $translation->getPlaceholder(),
                'options' => $translation->getOptions(),
            ];
        }

        return $result;
    }
}
