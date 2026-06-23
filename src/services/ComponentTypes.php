<?php

namespace justinholtweb\rabbits\services;

use craft\base\Component;
use justinholtweb\rabbits\events\RegisterComponentTypesEvent;
use justinholtweb\rabbits\models\NodeType;

/**
 * Registry of custom element types contributed by other plugins and modules.
 *
 * Listen to {@see self::EVENT_REGISTER_COMPONENT_TYPES} to add your own:
 *
 * ```php
 * use justinholtweb\rabbits\services\ComponentTypes;
 * use justinholtweb\rabbits\events\RegisterComponentTypesEvent;
 * use justinholtweb\rabbits\models\NodeType;
 * use yii\base\Event;
 *
 * Event::on(
 *     ComponentTypes::class,
 *     ComponentTypes::EVENT_REGISTER_COMPONENT_TYPES,
 *     function (RegisterComponentTypesEvent $event) {
 *         $event->types[] = new NodeType([
 *             'type' => 'rating',
 *             'label' => 'Star Rating',
 *             'icon' => 'star',
 *             'category' => 'Custom',
 *             'defaults' => ['stars' => 5],
 *             'fields' => [
 *                 ['name' => 'stars', 'label' => 'Stars', 'type' => 'number'],
 *             ],
 *             'render' => fn(array $node, string $attrs) =>
 *                 '<span' . $attrs . '>' . str_repeat('★', (int)($node['stars'] ?? 5)) . '</span>',
 *         ]);
 *     }
 * );
 * ```
 */
class ComponentTypes extends Component
{
    public const EVENT_REGISTER_COMPONENT_TYPES = 'registerComponentTypes';

    /** @var NodeType[]|null Lazily-built registry keyed by type. */
    private ?array $_types = null;

    /**
     * @return NodeType[] All registered custom types, keyed by their `type`.
     */
    public function getAll(): array
    {
        if ($this->_types === null) {
            $event = new RegisterComponentTypesEvent();
            $this->trigger(self::EVENT_REGISTER_COMPONENT_TYPES, $event);

            $registry = [];
            foreach ($event->types as $type) {
                if ($type instanceof NodeType && $type->type !== '') {
                    $registry[$type->type] = $type;
                }
            }
            $this->_types = $registry;
        }

        return $this->_types;
    }

    public function get(string $type): ?NodeType
    {
        return $this->getAll()[$type] ?? null;
    }

    public function has(string $type): bool
    {
        return isset($this->getAll()[$type]);
    }

    /**
     * Palette entries for the registered custom types.
     */
    public function getPaletteItems(): array
    {
        $items = [];
        foreach ($this->getAll() as $type) {
            $items[] = [
                'type' => $type->type,
                'label' => $type->label,
                'icon' => $type->icon ?: 'box',
                'category' => $type->category ?: 'Custom',
            ];
        }
        return $items;
    }

    /**
     * Client-safe type definitions for the builder UI (no render callable).
     */
    public function getClientDefinitions(): array
    {
        $defs = [];
        foreach ($this->getAll() as $type) {
            $defs[$type->type] = [
                'label' => $type->label,
                'icon' => $type->icon ?: 'box',
                'category' => $type->category ?: 'Custom',
                'fields' => $type->fields,
            ];
        }
        return $defs;
    }
}
