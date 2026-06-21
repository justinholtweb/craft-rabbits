<?php

namespace justinholtweb\rabbits\services;

use Craft;
use craft\base\Component as BaseComponent;
use justinholtweb\rabbits\elements\Component;

class Components extends BaseComponent
{
    public function getById(int $id): ?Component
    {
        return Component::find()->id($id)->one();
    }

    public function getByHandle(string $handle): ?Component
    {
        return Component::find()->handle($handle)->one();
    }

    public function save(Component $component): bool
    {
        if ($component->id === null) {
            // Compile Twig on first save
            $compiler = new TwigCompiler();
            $component->compiledTwig = $compiler->compile($component);
        }

        return Craft::$app->getElements()->saveElement($component);
    }

    public function delete(Component $component): bool
    {
        return Craft::$app->getElements()->deleteElement($component);
    }

    public function duplicate(int $componentId): ?Component
    {
        $original = $this->getById($componentId);

        if (!$original) {
            return null;
        }

        $duplicate = new Component();
        $duplicate->title = $original->title . ' (Copy)';
        $duplicate->handle = $this->uniqueHandle($original->handle . '_copy');
        $duplicate->componentType = $original->componentType;
        $duplicate->componentStatus = 'draft';
        $duplicate->tree = $original->tree;
        $duplicate->styles = $original->styles;
        $duplicate->animations = $original->animations;
        $duplicate->customCss = $original->customCss;
        $duplicate->customJs = $original->customJs;
        $duplicate->breakpoints = $original->breakpoints;

        if ($this->save($duplicate)) {
            return $duplicate;
        }

        return null;
    }

    /**
     * Return $base, or $base2, $base3… — whichever handle is not yet taken.
     */
    private function uniqueHandle(string $base): string
    {
        if (!$this->getByHandle($base)) {
            return $base;
        }

        $i = 2;
        while ($this->getByHandle($base . $i)) {
            $i++;
        }

        return $base . $i;
    }

    public function recompile(Component $component): bool
    {
        $compiler = new TwigCompiler();
        $component->compiledTwig = $compiler->compile($component);
        return $this->save($component);
    }

    public function recompileAll(): int
    {
        $components = Component::find()->all();
        $count = 0;

        foreach ($components as $component) {
            if ($this->recompile($component)) {
                $count++;
            }
        }

        return $count;
    }
}
