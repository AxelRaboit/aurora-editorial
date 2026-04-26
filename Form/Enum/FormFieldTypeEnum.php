<?php

declare(strict_types=1);

namespace App\Module\Editorial\Form\Enum;

enum FormFieldTypeEnum: string
{
    case Text = 'text';
    case Email = 'email';
    case Textarea = 'textarea';
    case Select = 'select';
    case Checkbox = 'checkbox';
    case Radio = 'radio';
    case Number = 'number';
    case Date = 'date';
    case Tel = 'tel';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Texte court',
            self::Email => 'Email',
            self::Textarea => 'Texte long',
            self::Select => 'Liste déroulante',
            self::Checkbox => 'Cases à cocher',
            self::Radio => 'Boutons radio',
            self::Number => 'Nombre',
            self::Date => 'Date',
            self::Tel => 'Téléphone',
        };
    }

    public function hasOptions(): bool
    {
        return in_array($this, [self::Select, self::Checkbox, self::Radio], true);
    }
}
