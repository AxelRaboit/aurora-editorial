<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Manager\Decorator;

use Aurora\Module\Editorial\Form\Contract\FormManagerInterface;
use Aurora\Module\Editorial\Form\DTO\FormFieldInput;
use Aurora\Module\Editorial\Form\DTO\FormInput;
use Aurora\Module\Editorial\Form\Entity\Form;
use Aurora\Module\Editorial\Form\Entity\FormField;
use Aurora\Module\Editorial\Form\Entity\FormSubmission;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[AsDecorator(decorates: FormManagerInterface::class)]
final readonly class RateLimitFormManagerDecorator implements FormManagerInterface
{
    public function __construct(
        #[AutowireDecorated]
        private FormManagerInterface $inner,
        private RateLimiterFactory $formSubmissionLimiter,
    ) {}

    public function create(FormInput $input): Form
    {
        return $this->inner->create($input);
    }

    public function update(Form $form, FormInput $input): void
    {
        $this->inner->update($form, $input);
    }

    public function delete(Form $form): void
    {
        $this->inner->delete($form);
    }

    public function createField(Form $form, FormFieldInput $input): FormField
    {
        return $this->inner->createField($form, $input);
    }

    public function updateField(FormField $field, FormFieldInput $input): void
    {
        $this->inner->updateField($field, $input);
    }

    public function deleteField(FormField $field): void
    {
        $this->inner->deleteField($field);
    }

    /** @param int[] $orderedIds */
    public function reorderFields(Form $form, array $orderedIds): void
    {
        $this->inner->reorderFields($form, $orderedIds);
    }

    /** @param array<string, mixed> $submittedData */
    public function submit(Form $form, array $submittedData, string $locale, string $ip): FormSubmission
    {
        $limiter = $this->formSubmissionLimiter->create($ip);
        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {
            throw new TooManyRequestsHttpException($limit->getRetryAfter()->getTimestamp() - time());
        }

        return $this->inner->submit($form, $submittedData, $locale, $ip);
    }
}
