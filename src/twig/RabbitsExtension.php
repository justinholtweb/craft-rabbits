<?php

namespace justinholtweb\rabbits\twig;

use justinholtweb\rabbits\Plugin;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

class RabbitsExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('rabbits_component', [$this, 'renderComponent'], ['is_safe' => ['html']]),
            new TwigFunction('rabbits_styles', [$this, 'renderStyles'], ['is_safe' => ['html']]),
            new TwigFunction('rabbits_animations', [$this, 'renderAnimations'], ['is_safe' => ['html']]),
            new TwigFunction('rabbits_alpine', [$this, 'renderAlpine'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Render a component by handle
     *
     * Usage: {{ rabbits_component('hero', { entry: entry }) }}
     */
    public function renderComponent(string $handle, array $variables = []): Markup
    {
        return Plugin::getInstance()->renderer->render($handle, $variables);
    }

    /**
     * Render CSS styles for components
     *
     * Usage: {{ rabbits_styles() }} or {{ rabbits_styles(['hero', 'footer']) }}
     */
    public function renderStyles(array $handles = []): Markup
    {
        return Plugin::getInstance()->renderer->getStyles($handles);
    }

    /**
     * Render the animation script
     *
     * Usage: {{ rabbits_animations() }}
     */
    public function renderAnimations(): Markup
    {
        return Plugin::getInstance()->renderer->getAnimationScript();
    }

    /**
     * Output the Alpine.js CDN script
     *
     * Usage: {{ rabbits_alpine() }}
     */
    public function renderAlpine(): Markup
    {
        return Plugin::getInstance()->renderer->getAlpineScript();
    }
}
