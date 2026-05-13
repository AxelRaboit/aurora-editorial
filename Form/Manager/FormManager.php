<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Manager;

use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Editorial\Form\Dto\FormFieldInputInterface;
use Aurora\Module\Editorial\Form\Dto\FormInputInterface;
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
use Aurora\Module\Editorial\Form\Event\FormSubmissionCreatedEvent;
use Aurora\Module\Editorial\Form\Repository\FormTranslationRepository;
use Aurora\Module\Editorial\Form\Service\FormNotificationService;
use Aurora\Module\Editorial\Form\Service\FormWebhookService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(FormManagerInterface::class)]
class FormManager implements FormManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly FormTranslationRepository $formTranslationRepository,
        protected readonly TranslatorInterface $translator,
        protected readonly FormNotificationService $notificationService,
        protected readonly FormWebhookService $webhookService,
        protected readonly SequenceGenerator $sequenceGenerator,
        protected readonly SettingRepository $settingRepository,
        protected readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function create(FormInputInterface $input): FormInterface
    {
        $form = $this->createForm();
        $this->applyInput($form, $input);
        $prefix = $this->settingRepository->get(ApplicationParameterEnum::EditorialFormPrefix->value, SequencePrefixEnum::Form->value) ?? SequencePrefixEnum::Form->value;
        $form->setReference($this->sequenceGenerator->next($prefix));
        $this->entityManager->persist($form);
        $this->entityManager->flush();

        return $form;
    }

    public function update(FormInterface $form, FormInputInterface $input): void
    {
        $this->applyInput($form, $input);
        $form->setUpdatedAt(new DateTimeImmutable());
        $this->entityManager->flush();
    }

    public function delete(FormInterface $form): void
    {
        $this->entityManager->remove($form);
        $this->entityManager->flush();
    }

    public function createField(FormInterface $form, FormFieldInputInterface $input): FormFieldInterface
    {
        $field = $this->createFormField();
        $field->setForm($form);
        $this->applyFieldInput($field, $input, $form->getFields()->count());
        $form->addField($field);
        $this->entityManager->persist($field);
        $form->setUpdatedAt(new DateTimeImmutable());
        $this->entityManager->flush();

        $fieldPrefix = $this->settingRepository->get(ApplicationParameterEnum::EditorialFormFieldPrefix->value, SequencePrefixEnum::FormField->value) ?? SequencePrefixEnum::FormField->value;
        $field->setReference($this->sequenceGenerator->next($fieldPrefix));
        $this->entityManager->flush();

        return $field;
    }

    public function updateField(FormFieldInterface $field, FormFieldInputInterface $input): void
    {
        $this->applyFieldInput($field, $input, $field->getPosition());
        $field->getForm()->setUpdatedAt(new DateTimeImmutable());
        $this->entityManager->flush();
    }

    public function deleteField(FormFieldInterface $field): void
    {
        $form = $field->getForm();
        $form->removeField($field);

        $this->entityManager->remove($field);
        $form->setUpdatedAt(new DateTimeImmutable());
        $this->entityManager->flush();
    }

    public function reorderFields(FormInterface $form, array $orderedIds): void
    {
        $fieldsById = [];
        foreach ($form->getFields() as $field) {
            $fieldsById[(int) $field->getId()] = $field;
        }

        foreach ($orderedIds as $position => $fieldId) {
            if (isset($fieldsById[$fieldId])) {
                $fieldsById[$fieldId]->setPosition($position);
            }
        }

        $form->setUpdatedAt(new DateTimeImmutable());
        $this->entityManager->flush();
    }

    public function findActiveTranslation(string $locale, string $slug): ?FormTranslationInterface
    {
        $translation = $this->formTranslationRepository->findOneByLocaleAndSlug($locale, $slug);
        if (!$translation instanceof FormTranslationInterface || !$translation->getForm()->isActive()) {
            return null;
        }

        return $translation;
    }

    public function submit(FormInterface $form, array $submittedData, string $locale, string $ip): FormSubmissionInterface
    {
        $submissionPrefix = $this->settingRepository->get(ApplicationParameterEnum::EditorialFormSubmissionPrefix->value, SequencePrefixEnum::FormSubmission->value) ?? SequencePrefixEnum::FormSubmission->value;

        $submission = $this->createFormSubmission();
        $submission->setForm($form);
        $submission->setData($submittedData);
        $submission->setLocale($locale);
        $submission->setIp($ip);
        $submission->setReference($this->sequenceGenerator->next($submissionPrefix));

        $this->entityManager->persist($submission);
        $this->entityManager->flush();

        $this->notificationService->notifyAdmin($form, $submission, $locale);
        $this->notificationService->notifyAuthorIfPresent($form, $submission, $locale);

        $this->eventDispatcher->dispatch(new FormSubmissionCreatedEvent($form, $submission));
        $this->webhookService->send($form, $submission, $locale);

        return $submission;
    }

    // ── Hooks: instanciation ──────────────────────────────────────────────────

    protected function createForm(): FormInterface
    {
        return new Form();
    }

    protected function createFormField(): FormFieldInterface
    {
        return new FormField();
    }

    protected function createFormTranslation(): FormTranslationInterface
    {
        return new FormTranslation();
    }

    protected function createFormFieldTranslation(): FormFieldTranslationInterface
    {
        return new FormFieldTranslation();
    }

    protected function createFormSubmission(): FormSubmissionInterface
    {
        return new FormSubmission();
    }

    // ── Hooks: hydratation ────────────────────────────────────────────────────

    protected function applyInput(FormInterface $form, FormInputInterface $input): void
    {
        $form->setNotifyEmail($input->getNotifyEmail());
        $form->setWebhookUrl($input->getWebhookUrl());
        $form->setCrmSync($input->isCrmSync());
        $form->setSteps($input->getSteps());
        $form->setActive($input->isActive());
        $this->applyTranslations($form, $input);
    }

    protected function applyFieldInput(FormFieldInterface $field, FormFieldInputInterface $input, int $position): void
    {
        $field->setType($input->getTypeEnum());
        $field->setRequired($input->isRequired());
        $field->setPosition($position);
        $field->setStep($input->getStep());
        $field->setConditions($input->getConditions());
        $field->setConditionsLogic($input->getConditionsLogic());
        $this->applyFieldTranslations($field, $input);
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    private function applyTranslations(FormInterface $form, FormInputInterface $input): void
    {
        $excludeId = $form->getId();

        foreach ($input->getTranslations() as $locale => $data) {
            $slug = $data['slug'];
            $this->assertSlugValid($locale, $slug, $excludeId);

            $translation = $form->getTranslation($locale);
            if (!$translation instanceof FormTranslationInterface) {
                $translation = $this->createFormTranslation();
                $translation->setLocale($locale);
                $form->addTranslation($translation);
                $this->entityManager->persist($translation);
            }

            $translation->setTitle($data['title']);
            $translation->setSlug($slug);
            $translation->setDescription($data['description']);
        }

        // Remove translations for locales no longer in the input
        $kept = $input->getTranslations();
        foreach ($form->getTranslations() as $existing) {
            if (!isset($kept[$existing->getLocale()])) {
                $form->removeTranslation($existing);
                $this->entityManager->remove($existing);
            }
        }
    }

    private function applyFieldTranslations(FormFieldInterface $field, FormFieldInputInterface $input): void
    {
        $kept = $input->getTranslations();
        foreach ($kept as $locale => $data) {
            $translation = $field->getTranslation($locale);
            if (!$translation instanceof FormFieldTranslationInterface) {
                $translation = $this->createFormFieldTranslation();
                $translation->setLocale($locale);
                $field->addTranslation($translation);
                $this->entityManager->persist($translation);
            }

            $translation->setLabel($data['label']);
            $translation->setPlaceholder($data['placeholder']);
            $translation->setOptions($data['options']);
        }

        // Remove translations for locales no longer in the input
        foreach ($field->getTranslations() as $existing) {
            if (!isset($kept[$existing->getLocale()])) {
                $field->removeTranslation($existing);
                $this->entityManager->remove($existing);
            }
        }
    }

    private function assertSlugValid(string $locale, string $slug, ?int $excludeFormId): void
    {
        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            throw new InvalidArgumentException(sprintf('translations.%s.slug|%s', $locale, $this->translator->trans('backend.forms.errors.slug_format')));
        }

        $existing = null === $excludeFormId
            ? $this->formTranslationRepository->findOneByLocaleAndSlug($locale, $slug)
            : $this->formTranslationRepository->findOneByLocaleAndSlugExcluding($locale, $slug, $excludeFormId);

        if ($existing instanceof FormTranslationInterface) {
            throw new InvalidArgumentException(sprintf('translations.%s.slug|%s', $locale, $this->translator->trans('backend.forms.errors.slug_taken')));
        }
    }
}
