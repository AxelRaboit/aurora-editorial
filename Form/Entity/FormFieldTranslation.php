<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'core_form_field_translations')]
#[ORM\UniqueConstraint(columns: ['field_id', 'locale'])]
class FormFieldTranslation extends AbstractFormFieldTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_form_field_translation_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
