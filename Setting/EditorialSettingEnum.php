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

    /**
     * Frontend rendering of each menu location, one switch per location so a
     * site can keep its footer nav while dropping the header one. Enforced in
     * {@see \Aurora\Module\Editorial\Menu\Twig\MenuExtension::menuItems()} —
     * an off location yields an empty list — so every template honours it
     * without asking. Hiding a single item instead lives on the item itself,
     * @see \Aurora\Module\Editorial\Menu\Enum\MenuItemVisibilityEnum::Hidden
     */
    case ShowPrimaryMenu = 'editorial_show_primary_menu';
    case ShowFooterMenu = 'editorial_show_footer_menu';
    case ShowAccountMenu = 'editorial_show_account_menu';

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
            self::ShowPrimaryMenu => 'backend.parameters.editorial_show_primary_menu.label',
            self::ShowFooterMenu => 'backend.parameters.editorial_show_footer_menu.label',
            self::ShowAccountMenu => 'backend.parameters.editorial_show_account_menu.label',
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
            self::ShowPrimaryMenu => 'backend.parameters.editorial_show_primary_menu.description',
            self::ShowFooterMenu => 'backend.parameters.editorial_show_footer_menu.description',
            self::ShowAccountMenu => 'backend.parameters.editorial_show_account_menu.description',
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
            self::ShowPrimaryMenu, self::ShowFooterMenu, self::ShowAccountMenu => '1',
        };
    }

    public function getType(): string
    {
        return match ($this) {
            self::ShowPrimaryMenu, self::ShowFooterMenu, self::ShowAccountMenu => 'bool',
            default => 'string',
        };
    }

    public function getGroup(): string
    {
        return match ($this) {
            self::ShowPrimaryMenu, self::ShowFooterMenu, self::ShowAccountMenu => 'navigation',
            default => 'sequences',
        };
    }

    /**
     * No placeholder by default — override on a per-case basis when an
     * example value is genuinely clearer than the description alone.
     */
    public function getPlaceholder(): ?string
    {
        return null;
    }
}
