<?php

namespace justinholtweb\rabbits\controllers;

use Craft;
use craft\web\Controller;
use justinholtweb\rabbits\Plugin;
use yii\web\Response;

/**
 * JSON API endpoints for the Vue builder
 */
class BuilderController extends Controller
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
     * Get the component tree for the builder
     */
    public function actionGetTree(): Response
    {
        $componentId = Craft::$app->getRequest()->getRequiredQueryParam('componentId');
        $component = Plugin::getInstance()->components->getById($componentId);

        if (!$component) {
            return $this->asJson(['error' => 'Component not found']);
        }

        return $this->asJson([
            'tree' => $component->getTreeArray(),
            'styles' => $component->getStylesArray(),
            'animations' => $component->getAnimationsArray(),
            'breakpoints' => $component->getBreakpointsArray(),
        ]);
    }

    /**
     * Save the component tree from the builder
     */
    public function actionSaveTree(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $componentId = $request->getRequiredBodyParam('componentId');
        $component = Plugin::getInstance()->components->getById($componentId);

        if (!$component) {
            return $this->asJson(['success' => false, 'error' => 'Component not found']);
        }

        $component->tree = $request->getBodyParam('tree', []);
        $component->styles = $request->getBodyParam('styles');
        $component->animations = $request->getBodyParam('animations');
        $component->breakpoints = $request->getBodyParam('breakpoints');
        $component->customCss = $request->getBodyParam('customCss');
        $component->customJs = $request->getBodyParam('customJs');

        // Recompile Twig
        $compiler = Plugin::getInstance()->compiler;
        $component->compiledTwig = $compiler->compile($component);

        if (Plugin::getInstance()->components->save($component)) {
            return $this->asJson(['success' => true]);
        }

        return $this->asJson([
            'success' => false,
            'errors' => $component->getErrors(),
        ]);
    }

    /**
     * Add a node to the tree
     */
    public function actionAddNode(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $componentId = $request->getRequiredBodyParam('componentId');
        $parentId = $request->getRequiredBodyParam('parentId');
        $nodeType = $request->getRequiredBodyParam('nodeType');
        $position = $request->getBodyParam('position');

        $component = Plugin::getInstance()->components->getById($componentId);

        if (!$component) {
            return $this->asJson(['success' => false, 'error' => 'Component not found']);
        }

        $builder = Plugin::getInstance()->builder;
        $tree = $component->getTreeArray();

        if (empty($tree)) {
            $tree = $builder->createRootNode();
        }

        $newNode = $builder->createNode($nodeType);
        $tree = $builder->addNode($tree, $parentId, $newNode, $position !== null ? (int) $position : null);

        $component->tree = $tree;

        // Recompile
        $compiler = Plugin::getInstance()->compiler;
        $component->compiledTwig = $compiler->compile($component);

        if (Plugin::getInstance()->components->save($component)) {
            return $this->asJson([
                'success' => true,
                'tree' => $tree,
                'newNode' => $newNode,
            ]);
        }

        return $this->asJson(['success' => false, 'errors' => $component->getErrors()]);
    }

    /**
     * Remove a node from the tree
     */
    public function actionRemoveNode(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $componentId = $request->getRequiredBodyParam('componentId');
        $nodeId = $request->getRequiredBodyParam('nodeId');

        $component = Plugin::getInstance()->components->getById($componentId);

        if (!$component) {
            return $this->asJson(['success' => false, 'error' => 'Component not found']);
        }

        $builder = Plugin::getInstance()->builder;
        $tree = $component->getTreeArray();
        $tree = $builder->removeNode($tree, $nodeId);

        $component->tree = $tree;

        $compiler = Plugin::getInstance()->compiler;
        $component->compiledTwig = $compiler->compile($component);

        if (Plugin::getInstance()->components->save($component)) {
            return $this->asJson(['success' => true, 'tree' => $tree]);
        }

        return $this->asJson(['success' => false, 'errors' => $component->getErrors()]);
    }

    /**
     * Move a node within the tree
     */
    public function actionMoveNode(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $componentId = $request->getRequiredBodyParam('componentId');
        $nodeId = $request->getRequiredBodyParam('nodeId');
        $newParentId = $request->getRequiredBodyParam('newParentId');
        $position = (int) $request->getRequiredBodyParam('position');

        $component = Plugin::getInstance()->components->getById($componentId);

        if (!$component) {
            return $this->asJson(['success' => false, 'error' => 'Component not found']);
        }

        $builder = Plugin::getInstance()->builder;
        $tree = $component->getTreeArray();
        $tree = $builder->moveNode($tree, $nodeId, $newParentId, $position);

        $component->tree = $tree;

        $compiler = Plugin::getInstance()->compiler;
        $component->compiledTwig = $compiler->compile($component);

        if (Plugin::getInstance()->components->save($component)) {
            return $this->asJson(['success' => true, 'tree' => $tree]);
        }

        return $this->asJson(['success' => false, 'errors' => $component->getErrors()]);
    }

    /**
     * Update a node's properties
     */
    public function actionUpdateNode(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $componentId = $request->getRequiredBodyParam('componentId');
        $nodeId = $request->getRequiredBodyParam('nodeId');
        $updates = $request->getRequiredBodyParam('updates');

        $component = Plugin::getInstance()->components->getById($componentId);

        if (!$component) {
            return $this->asJson(['success' => false, 'error' => 'Component not found']);
        }

        $builder = Plugin::getInstance()->builder;
        $tree = $component->getTreeArray();
        $tree = $builder->updateNode($tree, $nodeId, $updates);

        $component->tree = $tree;

        $compiler = Plugin::getInstance()->compiler;
        $component->compiledTwig = $compiler->compile($component);

        if (Plugin::getInstance()->components->save($component)) {
            return $this->asJson(['success' => true, 'tree' => $tree]);
        }

        return $this->asJson(['success' => false, 'errors' => $component->getErrors()]);
    }

    /**
     * Get the atom palette for the builder sidebar
     */
    public function actionGetPalette(): Response
    {
        $builder = Plugin::getInstance()->builder;
        $animations = Plugin::getInstance()->animations;

        return $this->asJson([
            'atoms' => $builder->getAtomPalette(),
            'animationPresets' => $animations->getPresets(),
            'animationTriggers' => $animations->getTriggers(),
        ]);
    }

    /**
     * Get design tokens for the builder
     */
    public function actionGetTokens(): Response
    {
        $themes = Plugin::getInstance()->themes;

        return $this->asJson([
            'tokens' => $themes->getTokens(),
            'tokensByCategory' => $themes->getTokensByCategory(),
            'source' => $themes->isSourdoughInstalled() ? 'sourdough' : 'rabbits',
        ]);
    }

    /**
     * Get the compiled Twig for inspection
     */
    public function actionGetCompiledTwig(): Response
    {
        $componentId = Craft::$app->getRequest()->getRequiredQueryParam('componentId');
        $component = Plugin::getInstance()->components->getById($componentId);

        if (!$component) {
            return $this->asJson(['error' => 'Component not found']);
        }

        return $this->asJson([
            'compiledTwig' => $component->compiledTwig,
        ]);
    }
}
