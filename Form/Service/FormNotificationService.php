<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Service;

use Aurora\Core\Mail\Service\MailService;
use Aurora\Module\Editorial\Form\Entity\Form;
use Aurora\Module\Editorial\Form\Entity\FormFieldTranslation;
use Aurora\Module\Editorial\Form\Entity\FormSubmission;
use Aurora\Module\Editorial\Form\Entity\FormTranslation;
use Aurora\Module\Editorial\Form\Enum\FormFieldTypeEnum;

final readonly class FormNotificationService
{
    public function __construct(private MailService $mail) {}

    /**
     * Sends the admin notification when a form is submitted.
     */
    public function notifyAdmin(Form $form, FormSubmission $submission, string $locale): void
    {
        $notifyEmail = (string) $form->getNotifyEmail();
        if ('' === $notifyEmail) {
            return;
        }

        $this->mail->send(
            $notifyEmail,
            'editorial.mail.form.subject_admin',
            '@Editorial/email/form_submission.html.twig',
            $this->buildContext($form, $submission, $locale),
            locale: $locale,
        );
    }

    /**
     * Sends a confirmation to the submitter when the form contains an email field
     * filled with a valid address.
     */
    public function notifyAuthorIfPresent(Form $form, FormSubmission $submission, string $locale): void
    {
        $submitterEmail = $this->extractSubmitterEmail($form, $submission);
        if (null === $submitterEmail) {
            return;
        }

        $this->mail->send(
            $submitterEmail,
            'editorial.mail.form.subject_confirmation',
            '@Editorial/email/form_submission_confirmation.html.twig',
            $this->buildContext($form, $submission, $locale),
            locale: $locale,
        );
    }

    private function extractSubmitterEmail(Form $form, FormSubmission $submission): ?string
    {
        foreach ($form->getFields() as $field) {
            if (FormFieldTypeEnum::Email !== $field->getType()) {
                continue;
            }

            $value = $submission->getData()[(string) $field->getId()] ?? null;
            if (is_string($value) && '' !== $value && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContext(Form $form, FormSubmission $submission, string $locale): array
    {
        $formTranslation = $this->resolve($form->getTranslation($locale), $form->getTranslations()->first());

        $rows = [];
        foreach ($form->getFields() as $field) {
            $fieldTranslation = $this->resolve($field->getTranslation($locale), $field->getTranslations()->first());
            $value = $submission->getData()[(string) $field->getId()] ?? '';
            $rows[] = [
                'label' => $fieldTranslation instanceof FormFieldTranslation ? $fieldTranslation->getLabel() : '#'.$field->getId(),
                'value' => is_array($value) ? implode(', ', $value) : (string) $value,
            ];
        }

        return [
            'form' => $form,
            'submission' => $submission,
            'formTitle' => $formTranslation instanceof FormTranslation ? $formTranslation->getTitle() : '',
            'rows' => $rows,
        ];
    }

    private function resolve(mixed $primary, mixed $fallback): mixed
    {
        return is_object($primary) ? $primary : (is_object($fallback) ? $fallback : null);
    }
}
