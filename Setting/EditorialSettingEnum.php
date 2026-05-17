<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Setting;

use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnumInterface;

enum EditorialSettingEnum: string implements ApplicationParameterEnumInterface
{
    case PostPrefix = 'editorial_post_prefix';
    case FormPrefix = 'editorial_form_prefix';
    case FormSubmissionPrefix = 'editorial_form_submission_prefix';
    case CommentPrefix = 'editorial_comment_prefix';
    case FormFieldPrefix = 'editorial_form_field_prefix';
    case TaxonomyTermPrefix = 'editorial_taxonomy_term_prefix';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::PostPrefix => 'backend.parameters.editorial_post_prefix.label',
            self::FormPrefix => 'backend.parameters.editorial_form_prefix.label',
            self::FormSubmissionPrefix => 'backend.parameters.editorial_form_submission_prefix.label',
            self::CommentPrefix => 'backend.parameters.editorial_comment_prefix.label',
            self::FormFieldPrefix => 'backend.parameters.editorial_form_field_prefix.label',
            self::TaxonomyTermPrefix => 'backend.parameters.editorial_taxonomy_term_prefix.label',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::PostPrefix => 'backend.parameters.editorial_post_prefix.description',
            self::FormPrefix => 'backend.parameters.editorial_form_prefix.description',
            self::FormSubmissionPrefix => 'backend.parameters.editorial_form_submission_prefix.description',
            self::CommentPrefix => 'backend.parameters.editorial_comment_prefix.description',
            self::FormFieldPrefix => 'backend.parameters.editorial_form_field_prefix.description',
            self::TaxonomyTermPrefix => 'backend.parameters.editorial_taxonomy_term_prefix.description',
        };
    }

    public function getDefaultValue(): string
    {
        return match ($this) {
            self::PostPrefix => SequencePrefixEnum::Post->value,
            self::FormPrefix => SequencePrefixEnum::Form->value,
            self::FormSubmissionPrefix => SequencePrefixEnum::FormSubmission->value,
            self::CommentPrefix => SequencePrefixEnum::Comment->value,
            self::FormFieldPrefix => SequencePrefixEnum::FormField->value,
            self::TaxonomyTermPrefix => SequencePrefixEnum::TaxonomyTerm->value,
        };
    }

    public function getType(): string
    {
        return 'string';
    }

    public function getGroup(): string
    {
        return 'sequences';
    }
}
