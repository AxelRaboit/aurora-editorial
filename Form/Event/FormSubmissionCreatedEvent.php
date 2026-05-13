<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Event;

use Aurora\Module\Editorial\Form\Entity\FormInterface;
use Aurora\Module\Editorial\Form\Entity\FormSubmissionInterface;

/**
 * Dispatched after a form submission has been persisted and flushed.
 * Mutable so that listeners (CRM sync, webhook, ...) can attach metadata.
 */
class FormSubmissionCreatedEvent
{
    public function __construct(
        private readonly FormInterface $form,
        private readonly FormSubmissionInterface $submission,
    ) {}

    public function getForm(): FormInterface
    {
        return $this->form;
    }

    public function getSubmission(): FormSubmissionInterface
    {
        return $this->submission;
    }
}
