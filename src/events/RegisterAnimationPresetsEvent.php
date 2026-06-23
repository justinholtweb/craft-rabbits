<?php

namespace justinholtweb\rabbits\events;

use yii\base\Event;

/**
 * Raised by the AnimationManager so plugins and modules can register their own
 * animation presets (or override the built-in ones).
 */
class RegisterAnimationPresetsEvent extends Event
{
    /**
     * @var array<string, array> Preset key => definition, where each definition
     *   is ['label' => string, 'keyframes' => array, 'options' => array].
     */
    public array $presets = [];
}
