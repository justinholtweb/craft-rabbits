<?php

namespace justinholtweb\rabbits\services;

use Craft;
use craft\base\Component;
use craft\helpers\Template;
use justinholtweb\rabbits\elements\Component as ComponentElement;
use justinholtweb\rabbits\Plugin;
use Twig\Markup;

/**
 * Runtime rendering service — renders components in Twig templates
 */
class Renderer extends Component
{
    private array $_cache = [];

    /**
     * Render a component by handle
     */
    public function render(string $handle, array $variables = []): Markup
    {
        $component = $this->getComponent($handle);

        if (!$component) {
            return Template::raw('<!-- Rabbits: component "' . htmlspecialchars($handle) . '" not found -->');
        }

        if ($component->componentStatus !== 'active') {
            return Template::raw('<!-- Rabbits: component "' . htmlspecialchars($handle) . '" is not active -->');
        }

        $twig = $component->compiledTwig;

        if (!$twig) {
            // Compile on-demand if not cached
            $compiler = new TwigCompiler();
            $twig = $compiler->compile($component);
        }

        // Render the compiled Twig string with provided variables
        $html = Craft::$app->getView()->renderString($twig, $variables);

        return Template::raw($html);
    }

    /**
     * Render a component by ID
     */
    public function renderById(int $id, array $variables = []): Markup
    {
        $component = Plugin::getInstance()->components->getById($id);

        if (!$component) {
            return Template::raw('<!-- Rabbits: component #' . $id . ' not found -->');
        }

        return $this->render($component->handle, $variables);
    }

    /**
     * Get aggregated CSS for all active components on a page
     */
    public function getStyles(array $handles = []): Markup
    {
        if (empty($handles)) {
            // Get all active components
            $components = ComponentElement::find()
                ->componentStatus('active')
                ->all();
        } else {
            $components = [];
            foreach ($handles as $handle) {
                $component = $this->getComponent($handle);
                if ($component) {
                    $components[] = $component;
                }
            }
        }

        $styleManager = Plugin::getInstance()->styles;
        $themeBridge = Plugin::getInstance()->themes;

        $css = [];

        // Base + interactive component styles
        $baseCss = Plugin::getInstance()->runtime->getBaseCss();
        if ($baseCss) {
            $css[] = $baseCss;
        }

        // Token CSS variables
        $tokensCss = $themeBridge->generateTokensCss();
        if ($tokensCss) {
            $css[] = $tokensCss;
        }

        // Component styles
        $componentCss = $styleManager->generateCss($components);
        if ($componentCss) {
            $css[] = $componentCss;
        }

        $output = '<style>' . implode("\n\n", $css) . '</style>';

        return Template::raw($output);
    }

    /**
     * Get the animation script tag
     */
    public function getAnimationScript(): Markup
    {
        $animationManager = Plugin::getInstance()->animations;
        $script = $animationManager->generateAnimationScript()
            . "\n" . Plugin::getInstance()->runtime->getScript();

        $out = '';
        if (Plugin::getInstance()->getSettings()->loadAlpine) {
            $out .= (string) $this->getAlpineScript();
        }
        $out .= '<script>' . $script . '</script>';

        return Template::raw($out);
    }

    /**
     * Get the Alpine.js CDN script tag
     */
    public function getAlpineScript(): Markup
    {
        // Pinned version + Subresource Integrity so a CDN compromise can't inject code.
        return Template::raw(
            '<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"'
            . ' integrity="sha384-l8f0VcPi/M1iHPv8egOnY/15TDwqgbOR1anMIJWvU6nLRgZVLTLSaNqi/TOoT5Fh"'
            . ' crossorigin="anonymous"></script>'
        );
    }

    /**
     * Get a component from cache or database
     */
    private function getComponent(string $handle): ?ComponentElement
    {
        if (!isset($this->_cache[$handle])) {
            $this->_cache[$handle] = Plugin::getInstance()->components->getByHandle($handle);
        }

        return $this->_cache[$handle];
    }
}
