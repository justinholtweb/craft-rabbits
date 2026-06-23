<?php

namespace justinholtweb\rabbits\events;

use justinholtweb\rabbits\models\NodeType;
use yii\base\Event;

/**
 * Raised by the ComponentTypes service so plugins and modules can register
 * their own element types for the Rabbits builder.
 */
class RegisterComponentTypesEvent extends Event
{
    /** @var NodeType[] The registered custom component types. */
    public array $types = [];
}
