# Extending Rabbits

Rabbits is built to be extended. Plugins and modules can register their own
element types, add animation presets, and rewrite compiled markup through a set
of events. This guide covers the public extension points.

- [Events](#events)
- [Registering a custom element type](#registering-a-custom-element-type)
- [The render callback](#the-render-callback)
- [Editable fields in the property panel](#editable-fields-in-the-property-panel)
- [Registering animation presets](#registering-animation-presets)
- [Rewriting compiled markup](#rewriting-compiled-markup)
- [Standard element events](#standard-element-events)

## Events

| Event | Class | Purpose |
| --- | --- | --- |
| `ComponentTypes::EVENT_REGISTER_COMPONENT_TYPES` | `RegisterComponentTypesEvent` | Add custom element types to the builder |
| `AnimationManager::EVENT_REGISTER_ANIMATION_PRESETS` | `RegisterAnimationPresetsEvent` | Add or override animation presets |
| `TwigCompiler::EVENT_COMPILE_NODE` | `CompileNodeEvent` | Inspect or rewrite any node's compiled markup |

Register handlers from your plugin/module `init()` (or a bootstrap):

```php
use yii\base\Event;
use justinholtweb\rabbits\services\ComponentTypes;
```

## Registering a custom element type

Listen to `EVENT_REGISTER_COMPONENT_TYPES` and add a `NodeType`. It appears in
the builder palette under its category, gets the defaults you provide when added,
and is rendered by your `render` callback at compile time.

```php
use justinholtweb\rabbits\services\ComponentTypes;
use justinholtweb\rabbits\events\RegisterComponentTypesEvent;
use justinholtweb\rabbits\models\NodeType;
use yii\base\Event;

Event::on(
    ComponentTypes::class,
    ComponentTypes::EVENT_REGISTER_COMPONENT_TYPES,
    function (RegisterComponentTypesEvent $event) {
        $event->types[] = new NodeType([
            'type' => 'rating',          // unique, lowercase-kebab
            'label' => 'Star Rating',    // palette label
            'icon' => 'star',            // a built-in feather icon key
            'category' => 'Custom',      // palette group
            'defaults' => [
                'stars' => 5,            // your own node data
            ],
            'fields' => [
                ['name' => 'stars', 'label' => 'Stars', 'type' => 'number'],
            ],
            'render' => function (array $node, string $attrs): string {
                $stars = (int)($node['stars'] ?? 5);
                return '<span' . $attrs . '>' . str_repeat('★', $stars) . '</span>';
            },
        ]);
    }
);
```

`NodeType` fields:

| Property | Type | Notes |
| --- | --- | --- |
| `type` | string | Unique key, `^[a-z][a-z0-9\-]*$` |
| `label` | string | Palette label |
| `icon` | string | Built-in feather icon key (defaults to `box`) |
| `category` | string | Palette group (defaults to `Custom`) |
| `defaults` | array | Default node data merged when the element is added |
| `fields` | array | Declarative property-panel fields (see below) |
| `render` | callable | Compiles the node to markup (see below) |

## The render callback

```php
function (array $node, string $attrs, callable $renderChildren): string
```

- **`$node`** — the node's data (your `defaults` plus anything edited in the panel).
- **`$attrs`** — a leading-space attribute string that already includes the node's
  `class`, inline `style`, `data-rabbits-node`, animation data attributes, and any
  custom attributes from the **Attributes** editor. Drop it straight into your tag
  so your element supports styling and animations for free.
- **`$renderChildren`** — `function (array $children): string`; compiles child
  nodes. Use it if your element is a container.

```php
'render' => function (array $node, string $attrs, callable $renderChildren): string {
    return '<section' . $attrs . '>' . $renderChildren($node['children'] ?? []) . '</section>';
},
```

The callback returns Twig/HTML. Because output is compiled (not rendered at
request time), you can emit Twig too — e.g. `{{ entry.title }}` or `{% for %}`.

If you omit `render`, the node compiles as a plain container.

## Editable fields in the property panel

Each entry in `fields` renders a generic control in the builder's property panel,
bound to `node[name]`:

```php
'fields' => [
    ['name' => 'stars',   'label' => 'Stars',   'type' => 'number'],
    ['name' => 'rounded', 'label' => 'Rounded', 'type' => 'checkbox'],
    ['name' => 'style',   'label' => 'Style',   'type' => 'select',
        'options' => [
            ['value' => 'solid', 'label' => 'Solid'],
            ['value' => 'outline', 'label' => 'Outline'],
        ],
    ],
    ['name' => 'caption', 'label' => 'Caption', 'type' => 'text'],
],
```

Supported `type`s: `text`, `number`, `checkbox`, `select`. Every custom element
also gets the built-in Classes, Styles, Attributes, and Animation controls.

## Registering animation presets

```php
use justinholtweb\rabbits\services\AnimationManager;
use justinholtweb\rabbits\events\RegisterAnimationPresetsEvent;
use yii\base\Event;

Event::on(
    AnimationManager::class,
    AnimationManager::EVENT_REGISTER_ANIMATION_PRESETS,
    function (RegisterAnimationPresetsEvent $event) {
        $event->presets['spin'] = [
            'label' => 'Spin',
            'keyframes' => [
                ['transform' => 'rotate(0)'],
                ['transform' => 'rotate(360deg)'],
            ],
            'options' => ['duration' => 800, 'easing' => 'ease-in-out'],
        ];
    }
);
```

Your preset appears in the Animation dropdown for every element. Keyframes and
options are passed straight to the Web Animations API.

## Rewriting compiled markup

`EVENT_COMPILE_NODE` fires for every node after it compiles. Modify `$event->html`
to rewrite the output — useful for wrapping, annotating, or injecting attributes.

```php
use justinholtweb\rabbits\services\TwigCompiler;
use justinholtweb\rabbits\events\CompileNodeEvent;
use yii\base\Event;

Event::on(
    TwigCompiler::class,
    TwigCompiler::EVENT_COMPILE_NODE,
    function (CompileNodeEvent $event) {
        if (($event->node['type'] ?? '') === 'image') {
            $event->html = str_replace('<img ', '<img data-zoomable ', $event->html);
        }
    }
);
```

## Standard element events

A Rabbits component is a Craft element (`justinholtweb\rabbits\elements\Component`),
so all the usual element events apply — for example to react to saves:

```php
use craft\base\Element;
use craft\events\ModelEvent;
use justinholtweb\rabbits\elements\Component;
use yii\base\Event;

Event::on(
    Component::class,
    Element::EVENT_AFTER_SAVE,
    function (ModelEvent $event) {
        /** @var Component $component */
        $component = $event->sender;
        // e.g. warm a cache, ping a webhook, etc.
    }
);
```
