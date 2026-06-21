<?php

namespace justinholtweb\rabbits\services;

use craft\base\Component;

/**
 * Serves the interactive-component runtime (JS) and base styles (CSS).
 *
 * The dist files are the single source of truth: the frontend loads them via
 * FrontendAsset, and the builder preview inlines them through this service.
 */
class Runtime extends Component
{
    private ?string $script = null;
    private ?string $baseCss = null;

    /**
     * The interactive components runtime JS (slider, popup, accordion, tabs).
     */
    public function getScript(): string
    {
        if ($this->script === null) {
            $this->script = $this->read('rabbits-components.js');
        }
        return $this->script;
    }

    /**
     * The base + interactive component CSS.
     */
    public function getBaseCss(): string
    {
        if ($this->baseCss === null) {
            $this->baseCss = $this->read('rabbits.css');
        }
        return $this->baseCss;
    }

    private function read(string $file): string
    {
        $path = __DIR__ . '/../web/assets/frontend/dist/' . $file;
        return is_file($path) ? (file_get_contents($path) ?: '') : '';
    }
}
