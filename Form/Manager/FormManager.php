<?php

declare(strict_types=1);

namespace App\Module\Editorial\Form\Manager;

use App\Core\Setting\Enum\ApplicationParameterEnum;
use App\Core\Setting\Repository\SettingRepository;
use App\Module\Editorial\Form\Contract\FormManagerInterface;
use App\Module\Editorial\Form\DTO\FormFieldInput;
use App\Module\Editorial\Form\DTO\FormInput;
use App\Module\Editorial\Form\Entity\Form;
use App\Module\Editorial\Form\Entity\FormField;
use App\Module\Editorial\Form\Entity\FormFieldTranslation;
use App\Module\Editorial\Form\Entity\FormSubmission;
use App\Module\Editorial\Form\Entity\FormTranslation;
use App\Module\Editorial\Form\Repository\FormTranslationRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(FormManagerInterface::class)]
final readonly class FormManager implements FormManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FormTranslationRepository $formTranslationRepository,
        private MailerInterface $mailer,
        private SettingRepository $settingRepository,
        private TranslatorInterface $translator,
        private string $mailerFrom,
    ) {}

    public function create(FormInput $input): Form
    {
        $form = new Form();
        $this->applySettings($form, $input);
        $this->applyTranslations($form, $input);
        $this->entityManager->persist($form);
        $this->entityManager->flush();

        return $form;
    }

    public function update(Form $form, FormInput $input): void
    {
        $this->applySettings($form, $input);
        $this->applyTranslations($form, $input);
        $form->setUpdatedAt(new DateTimeImmutable());
        $this->entityManager->flush();
    }

    public function delete(Form $form): void
    {
        $this->entityManager->remove($form);
        $this->entityManager->flush();
    }

    public function createField(Form $form, FormFieldInput $input): FormField
    {
        $field = new FormField();
        $field->setForm($form);
        $this->applyFieldSettings($field, $input, $form->getFields()->count());
        $this->applyFieldTranslations($field, $input);
        $form->addField($field);
        $this->entityManager->persist($field);
        $form->setUpdatedAt(new DateTimeImmutable());
        $this->entityManager->flush();

        return $field;
    }

    public function updateField(FormField $field, FormFieldInput $input): void
    {
        $this->applyFieldSettings($field, $input, $field->getPosition());
        $this->applyFieldTranslations($field, $input);
        $field->getForm()->setUpdatedAt(new DateTimeImmutable());
        $this->entityManager->flush();
    }

    public function deleteField(FormField $field): void
    {
        $form = $field->getForm();
        $form->removeField($field);

        $this->entityManager->remove($field);
        $form->setUpdatedAt(new DateTimeImmutable());
        $this->entityManager->flush();
    }

    public function reorderFields(Form $form, array $orderedIds): void
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

    public function submit(Form $form, array $submittedData, string $locale, string $ip): FormSubmission
    {
        $submission = new FormSubmission();
        $submission->setForm($form);
        $submission->setData($submittedData);
        $submission->setLocale($locale);
        $submission->setIp($ip);

        $this->entityManager->persist($submission);
        $this->entityManager->flush();

        if (null !== $form->getNotifyEmail()) {
            $this->sendNotification($form, $submission, $locale);
        }

        return $submission;
    }

    private function applySettings(Form $form, FormInput $input): void
    {
        $form->setNotifyEmail($input->notifyEmail);
        $form->setActive($input->active);
    }

    private function applyTranslations(Form $form, FormInput $input, ?int $excludeFormId = null): void
    {
        $excludeId = $excludeFormId ?? $form->getId();

        foreach ($input->translations as $locale => $data) {
            $slug = $data['slug'];
            $this->assertSlugValid($locale, $slug, $excludeId);

            $translation = $form->getTranslation($locale);
            if (!$translation instanceof FormTranslation) {
                $translation = new FormTranslation();
                $translation->setLocale($locale);
                $form->addTranslation($translation);
                $this->entityManager->persist($translation);
            }

            $translation->setTitle($data['title']);
            $translation->setSlug($slug);
            $translation->setDescription($data['description']);
        }

        // Remove translations for locales no longer in the input
        foreach ($form->getTranslations() as $existing) {
            if (!isset($input->translations[$existing->getLocale()])) {
                $form->removeTranslation($existing);
                $this->entityManager->remove($existing);
            }
        }
    }

    private function applyFieldSettings(FormField $field, FormFieldInput $input, int $position): void
    {
        $field->setType($input->getTypeEnum());
        $field->setRequired($input->required);
        $field->setPosition($position);
    }

    private function applyFieldTranslations(FormField $field, FormFieldInput $input): void
    {
        foreach ($input->translations as $locale => $data) {
            $translation = $field->getTranslation($locale);
            if (!$translation instanceof FormFieldTranslation) {
                $translation = new FormFieldTranslation();
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
            if (!isset($input->translations[$existing->getLocale()])) {
                $field->removeTranslation($existing);
                $this->entityManager->remove($existing);
            }
        }
    }

    private function assertSlugValid(string $locale, string $slug, ?int $excludeFormId): void
    {
        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            throw new InvalidArgumentException(sprintf('translations.%s.slug|%s', $locale, $this->translator->trans('admin.forms.errors.slug_format')));
        }

        $existing = null === $excludeFormId
            ? $this->formTranslationRepository->findOneByLocaleAndSlug($locale, $slug)
            : $this->formTranslationRepository->findOneByLocaleAndSlugExcluding($locale, $slug, $excludeFormId);

        if ($existing instanceof FormTranslation) {
            throw new InvalidArgumentException(sprintf('translations.%s.slug|%s', $locale, $this->translator->trans('admin.forms.errors.slug_taken')));
        }
    }

    private function sendNotification(Form $form, FormSubmission $submission, string $locale): void
    {
        $siteName = $this->settingRepository->getOrDefault(ApplicationParameterEnum::SiteName);
        $formTranslation = $this->resolveTranslation($form->getTranslation($locale), $form->getTranslations()->first());
        $formTitle = $formTranslation?->getTitle() ?? '';

        $rows = '';
        foreach ($form->getFields() as $field) {
            $fieldTranslation = $this->resolveTranslation($field->getTranslation($locale), $field->getTranslations()->first());
            $label = $fieldTranslation?->getLabel() ?? '#'.$field->getId();
            $value = $submission->getData()[(string) $field->getId()] ?? '';
            $displayValue = is_array($value) ? implode(', ', $value) : (string) $value;
            $rows .= sprintf(
                '<tr><td style="padding:6px 12px;font-weight:600;border-bottom:1px solid #eee;">%s</td><td style="padding:6px 12px;border-bottom:1px solid #eee;">%s</td></tr>',
                htmlspecialchars((string) $label),
                htmlspecialchars($displayValue),
            );
        }

        $html = sprintf(
            '<p>Une nouvelle soumission a été reçue pour le formulaire <strong>%s</strong>.</p><table border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%%">%s</table>',
            htmlspecialchars($formTitle),
            $rows,
        );

        $this->mailer->send((new Email())
            ->from($this->mailerFrom)
            ->to((string) $form->getNotifyEmail())
            ->subject(sprintf('[%s] Nouvelle soumission : %s', $siteName, $formTitle))
            ->html($html));
    }

    private function resolveTranslation(mixed $primary, mixed $fallback): mixed
    {
        return is_object($primary) ? $primary : (is_object($fallback) ? $fallback : null);
    }
}
