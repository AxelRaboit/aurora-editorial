<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Serializer;

use Aurora\Module\Editorial\Form\Entity\FormFieldInterface;
use Aurora\Module\Editorial\Form\Entity\FormFieldTranslationInterface;
use Aurora\Module\Editorial\Form\Entity\FormInterface;
use Aurora\Module\Editorial\Form\Entity\FormSubmissionInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\Translation\TranslatorInterface;

use const DATE_ATOM;

#[AsAlias(FormSerializerInterface::class)]
class FormSerializer implements FormSerializerInterface
{
    public function __construct(
        protected readonly TranslatorInterface $translator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function serialize(FormInterface $form, bool $withFields = true): array
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
    public function serializeField(FormFieldInterface $field): array
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
    public function serializeFieldForLocale(FormFieldInterface $field, string $locale): array
    {
        $translation = $field->getTranslation($locale);
        if (!$translation instanceof FormFieldTranslationInterface) {
            $first = $field->getTranslations()->first();
            $translation = $first instanceof FormFieldTranslationInterface ? $first : null;
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
    public function serializeSubmission(FormSubmissionInterface $submission): array
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
    protected function serializeFormTranslations(FormInterface $form): array
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
    protected function serializeFieldTranslations(FormFieldInterface $field): array
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
