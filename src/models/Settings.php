<?php

namespace justinholtweb\rabbits\models;

use craft\base\Model;

class Settings extends Model
{
    /** @var int Maximum components allowed (0 = unlimited) */
    public int $maxComponents = 0;

    /** @var bool Whether to cache compiled Twig output */
    public bool $enableTwigCache = true;

    /** @var int Cache duration in seconds (0 = forever) */
    public int $cacheDuration = 0;

    /** @var array Custom breakpoints */
    public array $breakpoints = [
        'desktop' => ['label' => 'Desktop', 'minWidth' => 1024, 'default' => true],
        'tablet' => ['label' => 'Tablet', 'minWidth' => 768, 'maxWidth' => 1023],
        'mobile' => ['label' => 'Mobile', 'maxWidth' => 767],
    ];

    /** @var bool Whether to enable custom CSS per component */
    public bool $enableCustomCss = true;

    /** @var bool Whether to enable custom JS per component */
    public bool $enableCustomJs = false;

    /** @var string Output directory for compiled Twig (relative to templates root) */
    public string $compiledPath = '_rabbits';

    public function defineRules(): array
    {
        return [
            [['maxComponents', 'cacheDuration'], 'integer', 'min' => 0],
            [['enableTwigCache', 'enableCustomCss', 'enableCustomJs'], 'boolean'],
            [['compiledPath'], 'string'],
        ];
    }
}
