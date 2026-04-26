<?php

declare(strict_types=1);

namespace App\Module\Editorial\Form\Controller\Front;

use App\Core\Frontend\Controller\FrontLocaleTrait;
use App\Core\Frontend\Service\FrontContext;
use App\Core\Theme\Service\ThemeContext;
use App\Core\Theme\Service\ThemeResolver;
use App\Module\Editorial\Form\Contract\FormManagerInterface;
use App\Module\Editorial\Form\Entity\FormTranslation;
use App\Module\Editorial\Form\Repository\FormTranslationRepository;
use App\Module\Editorial\Form\Serializer\FormSerializer;
use App\Module\Editorial\Form\Service\FormSubmissionValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FormController extends AbstractController
{
    use FrontLocaleTrait;

    public function __construct(
        private readonly FormTranslationRepository $formTranslationRepository,
        private readonly FormManagerInterface $formManager,
        private readonly FormSerializer $formSerializer,
        private readonly FormSubmissionValidator $formSubmissionValidator,
        private readonly FrontContext $frontContext,
        private readonly ThemeContext $themeContext,
        private readonly ThemeResolver $themeResolver,
    ) {}

    #[Route('/{locale}/forms/{slug}', name: 'front_form', requirements: ['locale' => '[a-z]{2}'], priority: 7)]
    public function show(string $locale, string $slug, Request $request): Response
    {
        $this->assertActiveLocale($this->frontContext, $locale);
        $request->setLocale($locale);

        $translation = $this->findActiveFormTranslation($locale, $slug);
        if (!$translation instanceof FormTranslation) {
            throw $this->createNotFoundException();
        }

        $form = $translation->getForm();
        $fields = array_values(array_map(
            fn ($field): array => $this->formSerializer->serializeFieldForLocale($field, $locale),
            $form->getFields()->toArray(),
        ));

        $response = $this->render($this->themeResolver->resolve('form'), [
            'locale' => $locale,
            'context' => $this->frontContext,
            'themeContext' => $this->themeContext,
            'form' => $form,
            'translation' => $translation,
            'fields' => $fields,
        ]);

        return $this->withI18nHeaders($response, $locale);
    }

    #[Route('/{locale}/forms/{slug}/submit', name: 'front_form_submit', requirements: ['locale' => '[a-z]{2}'], methods: ['POST'], priority: 8)]
    public function submit(string $locale, string $slug, Request $request): JsonResponse
    {
        $this->assertActiveLocale($this->frontContext, $locale);
        $request->setLocale($locale);

        $translation = $this->findActiveFormTranslation($locale, $slug);
        if (!$translation instanceof FormTranslation) {
            return $this->json(['ok' => false], Response::HTTP_NOT_FOUND);
        }

        $form = $translation->getForm();
        $payload = $request->toArray();

        $errors = $this->formSubmissionValidator->validate($form, $payload);
        if ([] !== $errors) {
            return $this->json(['ok' => false, 'errors' => $errors]);
        }

        $submittedData = $this->formSubmissionValidator->extractSubmittedData($form, $payload);
        $ip = $request->getClientIp() ?? '';
        $this->formManager->submit($form, $submittedData, $locale, $ip);

        return $this->json(['ok' => true]);
    }

    private function findActiveFormTranslation(string $locale, string $slug): ?FormTranslation
    {
        $translation = $this->formTranslationRepository->findOneByLocaleAndSlug($locale, $slug);
        if (!$translation instanceof FormTranslation || !$translation->getForm()->isActive()) {
            return null;
        }

        return $translation;
    }
}
