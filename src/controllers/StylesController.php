<?php

namespace justinholtweb\rabbits\controllers;

use Craft;
use craft\web\Controller;
use justinholtweb\rabbits\Plugin;
use yii\web\Response;

/**
 * Manages reusable style classes
 */
class StylesController extends Controller
{
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requirePermission('rabbits:manageComponents');
        return true;
    }

    /**
     * Get all style classes
     */
    public function actionIndex(): Response
    {
        $classes = Plugin::getInstance()->styles->getAllClasses();

        return $this->asJson([
            'classes' => array_map(fn($record) => [
                'id' => $record->id,
                'handle' => $record->handle,
                'name' => $record->name,
                'styles' => json_decode($record->styles, true) ?? [],
                'breakpoints' => json_decode($record->breakpoints, true) ?? [],
            ], $classes),
        ]);
    }

    /**
     * Save a style class
     */
    public function actionSave(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $handle = $request->getRequiredBodyParam('handle');
        $name = $request->getRequiredBodyParam('name');
        $styles = $request->getBodyParam('styles', []);
        $breakpoints = $request->getBodyParam('breakpoints', []);

        if (Plugin::getInstance()->styles->saveClass($handle, $name, $styles, $breakpoints)) {
            return $this->asJson(['success' => true]);
        }

        return $this->asJson(['success' => false, 'error' => 'Could not save class.']);
    }

    /**
     * Delete a style class
     */
    public function actionDelete(): Response
    {
        $this->requirePostRequest();

        $handle = Craft::$app->getRequest()->getRequiredBodyParam('handle');

        if (Plugin::getInstance()->styles->deleteClass($handle)) {
            return $this->asJson(['success' => true]);
        }

        return $this->asJson(['success' => false, 'error' => 'Could not delete class.']);
    }
}
