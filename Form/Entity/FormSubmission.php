<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Entity;

use Aurora\Module\Editorial\Form\Repository\FormSubmissionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FormSubmissionRepository::class)]
#[ORM\Table(name: 'core_form_submissions')]
class FormSubmission extends AbstractFormSubmission
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_form_submission_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
