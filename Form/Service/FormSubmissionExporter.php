<?php

declare(strict_types=1);

namespace App\Module\Editorial\Form\Service;

use App\Module\Editorial\Form\Entity\Form;
use App\Module\Editorial\Form\Entity\FormField;
use App\Module\Editorial\Form\Entity\FormFieldTranslation;
use App\Module\Editorial\Form\Entity\FormTranslation;
use App\Module\Editorial\Form\Repository\FormSubmissionRepository;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a CSV export of all submissions for a given form.
 * Headers come from the form fields' labels in the requested locale, with a
 * fallback to the first available translation.
 */
final readonly class FormSubmissionExporter
{
    public function __construct(private FormSubmissionRepository $submissionRepository) {}

    public function exportToCsv(Form $form, string $locale): StreamedResponse
    {
        $submissions = $this->submissionRepository->findAllByForm($form);
        $fields = $form->getFields()->toArray();
        $labels = $this->fieldLabels($fields, $locale);

        $response = new StreamedResponse(static function () use ($submissions, $labels, $fields): void {
            $handle = fopen('php://output', 'w');
            if (false === $handle) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, array_merge(['ID', 'Date', 'Locale', 'IP'], $labels), ';');
            foreach ($submissions as $submission) {
                $row = [
                    (string) $submission->getId(),
                    $submission->getSubmittedAt()->format('d/m/Y H:i:s'),
                    $submission->getLocale(),
                    (string) $submission->getIp(),
                ];
                foreach ($fields as $field) {
                    $value = $submission->getData()[(string) $field->getId()] ?? '';
                    $row[] = is_array($value) ? implode(', ', $value) : (string) $value;
                }

                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', sprintf(
            'attachment; filename="soumissions-%s-%s.csv"',
            $this->formSlug($form, $locale),
            date('Ymd'),
        ));

        return $response;
    }

    /**
     * @param list<FormField> $fields
     *
     * @return list<string>
     */
    private function fieldLabels(array $fields, string $locale): array
    {
        return array_map(static function (FormField $field) use ($locale): string {
            $translation = $field->getTranslation($locale);
            if (!$translation instanceof FormFieldTranslation) {
                $first = $field->getTranslations()->first();
                $translation = $first instanceof FormFieldTranslation ? $first : null;
            }

            return $translation?->getLabel() ?? '#'.$field->getId();
        }, $fields);
    }

    private function formSlug(Form $form, string $locale): string
    {
        $translation = $form->getTranslation($locale);
        if (!$translation instanceof FormTranslation) {
            $first = $form->getTranslations()->first();
            $translation = $first instanceof FormTranslation ? $first : null;
        }

        return $translation?->getSlug() ?? (string) $form->getId();
    }
}
