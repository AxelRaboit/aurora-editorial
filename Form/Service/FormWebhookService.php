<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Service;

use Aurora\Module\Editorial\Form\Entity\FormFieldTranslationInterface;
use Aurora\Module\Editorial\Form\Entity\FormInterface;
use Aurora\Module\Editorial\Form\Entity\FormSubmissionInterface;
use DateTimeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

/**
 * Sends a POST request to the form's webhookUrl after a submission.
 *
 * Payload format:
 * {
 *   "event": "form.submitted",
 *   "form": {"id": 1, "slug": "contact"},
 *   "submission": {"reference": "SUB-0001", "locale": "fr", "submittedAt": "..."},
 *   "fields": [{"label": "Nom", "value": "Pierre"}]
 * }
 */
final readonly class FormWebhookService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {}

    public function send(FormInterface $form, FormSubmissionInterface $submission, string $locale): void
    {
        $url = $form->getWebhookUrl();
        if (null === $url || '' === $url) {
            return;
        }

        $payload = $this->buildPayload($form, $submission, $locale);

        try {
            $this->httpClient->request('POST', $url, [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'timeout' => 5,
            ]);
        } catch (Throwable $throwable) {
            $this->logger->warning('Form webhook delivery failed.', [
                'url' => $url,
                'form' => $form->getId(),
                'submission' => $submission->getReference(),
                'error' => $throwable->getMessage(),
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function buildPayload(FormInterface $form, FormSubmissionInterface $submission, string $locale): array
    {
        $formTranslation = $form->getTranslation($locale) ?? $form->getTranslations()->first() ?: null;
        $data = $submission->getData();

        $fields = [];
        foreach ($form->getFields() as $field) {
            $fieldTranslation = $field->getTranslation($locale);
            if (!$fieldTranslation instanceof FormFieldTranslationInterface) {
                $first = $field->getTranslations()->first();
                $fieldTranslation = $first instanceof FormFieldTranslationInterface ? $first : null;
            }

            $label = $fieldTranslation?->getLabel() ?? (string) $field->getId();
            $value = $data[(string) $field->getId()] ?? null;
            $fields[] = ['label' => $label, 'value' => $value];
        }

        return [
            'event' => 'form.submitted',
            'form' => [
                'id' => $form->getId(),
                'slug' => $formTranslation?->getSlug() ?? '',
                'title' => $formTranslation?->getTitle() ?? '',
            ],
            'submission' => [
                'reference' => $submission->getReference(),
                'locale' => $submission->getLocale(),
                'submittedAt' => $submission->getSubmittedAt()->format(DateTimeInterface::ATOM),
                'ip' => $submission->getIp(),
            ],
            'fields' => $fields,
        ];
    }
}
