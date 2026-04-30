<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Form\Controller\Front;

use Aurora\Core\Frontend\Controller\FrontLocaleTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Frontend\Service\FrontContext;
use Aurora\Core\Theme\Service\ThemeResolver;
use Aurora\Module\Editorial\Form\Contract\FormManagerInterface;
use Aurora\Module\Editorial\Form\Entity\FormTranslation;
use Aurora\Module\Editorial\Form\Repository\FormTranslationRepository;
use Aurora\Module\Editorial\Form\Service\FormSubmissionValidator;
use Aurora\Module\Editorial\Form\View\FormViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FormController extends AbstractController
{
    use FrontLocaleTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly FormTranslationRepository $formTranslationRepository,
        private readonly FormManagerInterface $formManager,
        private readonly FormSubmissionValidator $formSubmissionValidator,
        private readonly FrontContext $frontContext,
        private readonly ThemeResolver $themeResolver,
        private readonly FormViewBuilder $viewBuilder,
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

        $response = $this->render($this->themeResolver->resolve('form'), $this->viewBuilder->showView($translation, $locale));

        return $this->withI18nHeaders($response, $locale);
    }

    #[Route('/{locale}/forms/{slug}/submit', name: 'front_form_submit', requirements: ['locale' => '[a-z]{2}'], methods: ['POST'], priority: 8)]
    public function submit(string $locale, string $slug, Request $request): JsonResponse
    {
        $this->assertActiveLocale($this->frontContext, $locale);
        $request->setLocale($locale);

        $translation = $this->findActiveFormTranslation($locale, $slug);
        if (!$translation instanceof FormTranslation) {
            return $this->json(['success' => false], Response::HTTP_NOT_FOUND);
        }

        $form = $translation->getForm();
        $payload = $request->toArray();

        $errors = $this->formSubmissionValidator->validate($form, $payload);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        $submittedData = $this->formSubmissionValidator->extractSubmittedData($form, $payload);
        $ip = $request->getClientIp() ?? '';
        $this->formManager->submit($form, $submittedData, $locale, $ip);

        return $this->jsonSuccess();
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
