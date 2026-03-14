<?php

namespace justinholtweb\rabbits\services;

use craft\base\Component;
use craft\helpers\Json;
use justinholtweb\rabbits\elements\Component as ComponentElement;
use justinholtweb\rabbits\Plugin;
use justinholtweb\rabbits\records\ClassRecord;

/**
 * Manages the class system and CSS aggregation
 */
class StyleManager extends Component
{
    /**
     * Get all saved style classes
     */
    public function getAllClasses(): array
    {
        return ClassRecord::find()->orderBy(['name' => SORT_ASC])->all();
    }

    /**
     * Get a class by handle
     */
    public function getClassByHandle(string $handle): ?ClassRecord
    {
        return ClassRecord::findOne(['handle' => $handle]);
    }

    /**
     * Save a style class
     */
    public function saveClass(string $handle, string $name, array $styles, array $breakpoints = []): bool
    {
        $record = ClassRecord::findOne(['handle' => $handle]) ?? new ClassRecord();

        $record->handle = $handle;
        $record->name = $name;
        $record->styles = Json::encode($styles);
        $record->breakpoints = Json::encode($breakpoints);

        return $record->save();
    }

    /**
     * Delete a style class
     */
    public function deleteClass(string $handle): bool
    {
        $record = ClassRecord::findOne(['handle' => $handle]);
        return $record ? $record->delete() !== false : false;
    }

    /**
     * Generate aggregated CSS for a set of components
     *
     * Generates scoped CSS with responsive breakpoints
     */
    public function generateCss(array $components): string
    {
        $css = [];
        $css[] = '/* Rabbits Component Styles */';
        $css[] = '';

        $settings = Plugin::getInstance()->getSettings();
        $breakpoints = $settings->breakpoints;

        foreach ($components as $component) {
            if (!$component instanceof ComponentElement) {
                continue;
            }

            $componentCss = $this->generateComponentCss($component, $breakpoints);
            if ($componentCss) {
                $css[] = '/* Component: ' . $component->handle . ' */';
                $css[] = $componentCss;
                $css[] = '';
            }
        }

        return implode("\n", $css);
    }

    /**
     * Generate CSS for a single component
     */
    public function generateComponentCss(ComponentElement $component, array $breakpoints): string
    {
        $tree = $component->getTreeArray();

        if (empty($tree)) {
            return '';
        }

        $rules = [];
        $this->collectStyleRules($tree, $rules);

        $css = [];

        // Default styles
        foreach ($rules as $nodeId => $nodeStyles) {
            $defaultStyles = $nodeStyles['default'] ?? [];
            if (!empty($defaultStyles)) {
                $css[] = $this->formatRule("[data-rabbits-node=\"{$nodeId}\"]", $defaultStyles);
            }
        }

        // Responsive breakpoints
        foreach ($breakpoints as $bpKey => $bp) {
            if ($bpKey === 'desktop' || ($bp['default'] ?? false)) {
                continue;
            }

            $bpRules = [];
            foreach ($rules as $nodeId => $nodeStyles) {
                $bpStyles = $nodeStyles[$bpKey] ?? [];
                if (!empty($bpStyles)) {
                    $bpRules[] = $this->formatRule("[data-rabbits-node=\"{$nodeId}\"]", $bpStyles);
                }
            }

            if (!empty($bpRules)) {
                $mediaQuery = $this->buildMediaQuery($bp);
                $css[] = "@media {$mediaQuery} {";
                $css[] = implode("\n", $bpRules);
                $css[] = '}';
            }
        }

        // Custom CSS
        if (!empty($component->customCss)) {
            $css[] = $component->customCss;
        }

        return implode("\n", $css);
    }

    /**
     * Collect style rules from the tree recursively
     */
    private function collectStyleRules(array $node, array &$rules): void
    {
        $styles = $node['styles'] ?? [];

        if (!empty($styles) && isset($node['id'])) {
            $rules[$node['id']] = $styles;
        }

        if (isset($node['children'])) {
            foreach ($node['children'] as $child) {
                $this->collectStyleRules($child, $rules);
            }
        }
    }

    /**
     * Format a CSS rule
     */
    private function formatRule(string $selector, array $styles): string
    {
        $declarations = [];
        foreach ($styles as $property => $value) {
            $cssProperty = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $property));
            $declarations[] = "  {$cssProperty}: {$value};";
        }

        return "{$selector} {\n" . implode("\n", $declarations) . "\n}";
    }

    /**
     * Build a CSS media query from breakpoint config
     */
    private function buildMediaQuery(array $breakpoint): string
    {
        $conditions = [];

        if (isset($breakpoint['maxWidth'])) {
            $conditions[] = "(max-width: {$breakpoint['maxWidth']}px)";
        }

        if (isset($breakpoint['minWidth'])) {
            $conditions[] = "(min-width: {$breakpoint['minWidth']}px)";
        }

        return implode(' and ', $conditions);
    }
}
