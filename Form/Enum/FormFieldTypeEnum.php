<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Enum;

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

    public function getLabelKey(): string
    {
        return 'backend.editorial.forms.field_type.'.$this->value;
    }

    public function hasOptions(): bool
    {
        return in_array($this, [self::Select, self::Checkbox, self::Radio], true);
    }
}
