<?php

namespace justinholtweb\rabbits\controllers;

use Craft;
use craft\web\Controller;
use justinholtweb\rabbits\Plugin;
use yii\web\Response;

/**
 * Renders live preview of components for the builder iframe
 */
class PreviewController extends Controller
{
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requirePermission('rabbits:accessPlugin');
        return true;
    }

    /**
     * Render a component preview for the builder iframe
     */
    public function actionRender(): Response
    {
        $componentId = Craft::$app->getRequest()->getParam('componentId');

        if (!$componentId) {
            return $this->renderTemplate('rabbits/_cp/preview/empty');
        }

        $component = Plugin::getInstance()->components->getById($componentId);

        if (!$component) {
            return $this->renderTemplate('rabbits/_cp/preview/not-found');
        }

        $compiler = Plugin::getInstance()->compiler;
        $twig = $compiler->compile($component);

        $styleManager = Plugin::getInstance()->styles;
        $themeBridge = Plugin::getInstance()->themes;

        $css = [];
        $tokensCss = $themeBridge->generateTokensCss();
        if ($tokensCss) {
            $css[] = $tokensCss;
        }

        $settings = Plugin::getInstance()->getSettings();
        $componentCss = $styleManager->generateComponentCss($component, $settings->breakpoints);
        if ($componentCss) {
            $css[] = $componentCss;
        }

        // Render the Twig with sample variables
        $variables = $this->getSampleVariables();
        $renderedHtml = Craft::$app->getView()->renderString($twig, $variables);

        return $this->renderTemplate('rabbits/_cp/preview/frame', [
            'component' => $component,
            'renderedHtml' => $renderedHtml,
            'css' => implode("\n\n", $css),
        ]);
    }

    /**
     * Quick preview — render from posted tree without saving
     */
    public function actionQuick(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $tree = $request->getBodyParam('tree', []);

        if (empty($tree)) {
            return $this->asJson(['html' => '<!-- Empty tree -->']);
        }

        // Create a temporary component for compilation
        $tempComponent = new \justinholtweb\rabbits\elements\Component();
        $tempComponent->tree = $tree;
        $tempComponent->handle = 'preview';
        $tempComponent->title = 'Preview';

        $compiler = Plugin::getInstance()->compiler;
        $twig = $compiler->compile($tempComponent);

        $variables = $this->getSampleVariables();
        $html = Craft::$app->getView()->renderString($twig, $variables);

        return $this->asJson(['html' => $html]);
    }

    /**
     * Get sample variables for preview rendering
     */
    private function getSampleVariables(): array
    {
        return [
            'entry' => (object) [
                'title' => 'Sample Page Title',
                'url' => '/sample-page',
            ],
        ];
    }
}
