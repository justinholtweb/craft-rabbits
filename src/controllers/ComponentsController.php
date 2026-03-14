<?php

namespace justinholtweb\rabbits\controllers;

use Craft;
use craft\web\Controller;
use justinholtweb\rabbits\elements\Component;
use justinholtweb\rabbits\enums\ComponentStatus;
use justinholtweb\rabbits\enums\ComponentType;
use justinholtweb\rabbits\Plugin;
use yii\web\Response;

class ComponentsController extends Controller
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
     * Component element index
     */
    public function actionIndex(): Response
    {
        return $this->renderTemplate('rabbits/_cp/components/index');
    }

    /**
     * New component form
     */
    public function actionNew(): Response
    {
        $component = new Component();

        return $this->renderTemplate('rabbits/_cp/components/edit', [
            'component' => $component,
            'isNew' => true,
            'componentTypes' => ComponentType::cases(),
            'componentStatuses' => ComponentStatus::cases(),
        ]);
    }

    /**
     * Edit component form
     */
    public function actionEdit(int $componentId): Response
    {
        $component = Plugin::getInstance()->components->getById($componentId);

        if (!$component) {
            throw new \yii\web\NotFoundHttpException('Component not found.');
        }

        return $this->renderTemplate('rabbits/_cp/components/edit', [
            'component' => $component,
            'isNew' => false,
            'componentTypes' => ComponentType::cases(),
            'componentStatuses' => ComponentStatus::cases(),
        ]);
    }

    /**
     * Save a component
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $this->requirePermission('rabbits:manageComponents');

        $request = Craft::$app->getRequest();
        $componentId = $request->getBodyParam('componentId');

        if ($componentId) {
            $component = Plugin::getInstance()->components->getById($componentId);

            if (!$component) {
                throw new \yii\web\NotFoundHttpException('Component not found.');
            }
        } else {
            $component = new Component();
        }

        $component->title = $request->getBodyParam('title');
        $component->handle = $request->getBodyParam('handle');
        $component->componentType = $request->getBodyParam('componentType', 'atom');
        $component->componentStatus = $request->getBodyParam('componentStatus', 'draft');

        // Tree/styles/animations come from the builder API, not the form
        if ($request->getBodyParam('tree')) {
            $component->tree = $request->getBodyParam('tree');
        }

        if (Plugin::getInstance()->components->save($component)) {
            Craft::$app->getSession()->setNotice(Craft::t('rabbits', 'Component saved.'));
            return $this->redirectToPostedUrl($component);
        }

        Craft::$app->getSession()->setError(Craft::t('rabbits', 'Could not save component.'));

        return $this->renderTemplate('rabbits/_cp/components/edit', [
            'component' => $component,
            'isNew' => !$componentId,
            'componentTypes' => ComponentType::cases(),
            'componentStatuses' => ComponentStatus::cases(),
        ]);
    }

    /**
     * Duplicate a component
     */
    public function actionDuplicate(): Response
    {
        $this->requirePostRequest();
        $this->requirePermission('rabbits:manageComponents');

        $componentId = Craft::$app->getRequest()->getRequiredBodyParam('componentId');
        $duplicate = Plugin::getInstance()->components->duplicate($componentId);

        if ($duplicate) {
            Craft::$app->getSession()->setNotice(Craft::t('rabbits', 'Component duplicated.'));
            return $this->redirect('rabbits/components/' . $duplicate->id);
        }

        Craft::$app->getSession()->setError(Craft::t('rabbits', 'Could not duplicate component.'));
        return $this->redirect('rabbits/components');
    }
}
