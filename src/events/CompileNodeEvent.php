<?php

namespace justinholtweb\rabbits\events;

use yii\base\Event;

/**
 * Raised by the TwigCompiler for every node after it has been compiled, so
 * plugins and modules can inspect or rewrite the generated markup.
 */
class CompileNodeEvent extends Event
{
    /** @var array The node tree being compiled (read-only). */
    public array $node = [];

    /** @var string The compiled markup for the node. Modify to rewrite it. */
    public string $html = '';

    /** @var int The node's depth in the tree. */
    public int $depth = 0;
}
