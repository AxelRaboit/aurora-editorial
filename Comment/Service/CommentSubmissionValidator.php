<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Comment\Service;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates the raw fields of a public comment submission. Returns a flat
 * field → translation-key map so the controller can simply forward it to
 * the response (frontend translates on display).
 */
final readonly class CommentSubmissionValidator
{
    public function __construct(private ValidatorInterface $validator) {}

    /**
     * @return array<string, string>
     */
    public function validate(string $authorName, string $authorEmail, string $content): array
    {
        $errors = [];

        $errors = $this->collectFirst($errors, 'authorName', $authorName, [
            new NotBlank(message: 'comment.errors.name_required'),
            new Length(max: 100),
        ]);

        $errors = $this->collectFirst($errors, 'authorEmail', $authorEmail, [
            new NotBlank(message: 'comment.errors.email_invalid'),
            new Email(message: 'comment.errors.email_invalid'),
        ]);

        return $this->collectFirst($errors, 'content', $content, [
            new NotBlank(message: 'comment.errors.content_required'),
            new Length(max: 2000, maxMessage: 'comment.errors.content_too_long'),
        ]);
    }

    /**
     * @param array<string, string> $errors
     * @param list<Constraint>      $constraints
     *
     * @return array<string, string>
     */
    private function collectFirst(array $errors, string $field, string $value, array $constraints): array
    {
        foreach ($this->validator->validate($value, $constraints) as $violation) {
            $errors[$field] = (string) $violation->getMessage();

            break;
        }

        return $errors;
    }
}
