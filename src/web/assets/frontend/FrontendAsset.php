<?php

namespace justinholtweb\rabbits\web\assets\frontend;

use craft\web\AssetBundle;

class FrontendAsset extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = __DIR__ . '/dist';

        $this->js = [
            'rabbits-animations.js',
            'rabbits-components.js',
        ];

        $this->css = [
            'rabbits.css',
        ];

        parent::init();
    }
}
