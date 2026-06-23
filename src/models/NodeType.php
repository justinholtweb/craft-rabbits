<?php

namespace justinholtweb\rabbits\models;

use craft\base\Model;

/**
 * Describes a custom element type registered with the Rabbits builder.
 *
 * Register one from the ComponentTypes::EVENT_REGISTER_COMPONENT_TYPES event.
 */
class NodeType extends Model
{
    /** @var string Unique element type key, e.g. `my-pricing-table`. */
    public string $type = '';

    /** @var string Palette label. */
    public string $label = '';

    /** @var string Icon name — a Rabbits built-in feather icon key (e.g. `box`). */
    public string $icon = 'box';

    /** @var string Palette category the element is grouped under. */
    public string $category = 'Custom';

    /**
     * @var array Default node shape applied when the element is added. Merged
     *   over a base of `tag`/`classes`/`styles`/`children`; the `type` key is
     *   always set automatically.
     */
    public array $defaults = [];

    /**
     * @var array Declarative editable fields shown in the property panel. Each
     *   entry: `['name' => string, 'label' => string, 'type' => 'text'|'number'
     *   |'checkbox'|'select', 'options' => array]` (options only for `select`).
     */
    public array $fields = [];

    /**
     * @var callable|null Renders the node to markup. Signature:
     *   `function (array $node, string $attrs, callable $renderChildren): string`
     *   - `$node`           the node tree
     *   - `$attrs`          leading-space attribute string (class/style/data/
     *                       animation/custom attributes) to drop into your tag
     *   - `$renderChildren` `function (array $children): string` — compiles
     *                       child nodes
     *   When omitted, the node compiles as a plain container.
     */
    public $render = null;

    protected function defineRules(): array
    {
        return [
            [['type', 'label'], 'required'],
            [['type'], 'match', 'pattern' => '/^[a-z][a-z0-9\-]*$/'],
        ];
    }
}
