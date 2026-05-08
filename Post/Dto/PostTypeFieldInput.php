<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Dto;

use Aurora\Core\Support\Str;
use Aurora\Module\Editorial\Post\Entity\PostTypeField;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class PostTypeFieldInput
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        #[Assert\NotBlank(message: 'post_types.errors.field_name_required')]
        #[Assert\Regex(pattern: '/^[a-z0-9_]+$/', message: 'post_types.errors.field_name_format')]
        #[Assert\Length(max: 100)]
        public string $name,
        #[Assert\NotBlank(message: 'post_types.errors.field_label_required')]
        #[Assert\Length(max: 100)]
        public string $label,
        #[Assert\Choice(choices: PostTypeField::TYPES, message: 'post_types.errors.field_type_invalid')]
        public string $type,
        public bool $required = false,
        public bool $translatable = false,
        public array $options = [],
    ) {}

    public static function fromArray(array $data): self
    {
        $rawOptions = is_array($data['options'] ?? null) ? $data['options'] : [];

        return new self(
            name: mb_strtolower(Str::trimOrNull((string) ($data['name'] ?? '')) ?? ''),
            label: Str::trimOrNull((string) ($data['label'] ?? '')) ?? '',
            type: Str::trimOrNull((string) ($data['type'] ?? '')) ?? 'text',
            required: (bool) ($data['required'] ?? false),
            translatable: (bool) ($data['translatable'] ?? false),
            options: $rawOptions,
        );
    }
}
