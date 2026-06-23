<?php

namespace justinholtweb\rabbits\services;

use craft\base\Component;
use craft\helpers\StringHelper;

/**
 * Tree manipulation service for the visual builder
 */
class Builder extends Component
{
    /**
     * Create a new node with defaults for the given type
     */
    public function createNode(string $type, array $overrides = []): array
    {
        $defaults = $this->getNodeDefaults($type);

        return array_merge($defaults, $overrides, [
            'id' => 'node_' . StringHelper::randomString(8),
        ]);
    }

    /**
     * Add a node as a child of the target node in the tree
     */
    public function addNode(array $tree, string $parentId, array $node, ?int $position = null): array
    {
        if ($tree['id'] === $parentId) {
            $children = $tree['children'] ?? [];
            if ($position !== null && $position >= 0 && $position <= count($children)) {
                array_splice($children, $position, 0, [$node]);
            } else {
                $children[] = $node;
            }
            $tree['children'] = $children;
            return $tree;
        }

        if (isset($tree['children'])) {
            foreach ($tree['children'] as $i => $child) {
                $tree['children'][$i] = $this->addNode($child, $parentId, $node, $position);
            }
        }

        return $tree;
    }

    /**
     * Remove a node from the tree by ID
     */
    public function removeNode(array $tree, string $nodeId): array
    {
        if (isset($tree['children'])) {
            $tree['children'] = array_values(array_filter(
                $tree['children'],
                fn(array $child) => $child['id'] !== $nodeId
            ));

            foreach ($tree['children'] as $i => $child) {
                $tree['children'][$i] = $this->removeNode($child, $nodeId);
            }
        }

        return $tree;
    }

    /**
     * Move a node to a new parent/position
     */
    public function moveNode(array $tree, string $nodeId, string $newParentId, int $position): array
    {
        $node = $this->findNode($tree, $nodeId);

        if (!$node) {
            return $tree;
        }

        // Refuse to move a node into itself or one of its own descendants,
        // which would orphan the subtree (remove succeeds, re-add can't find the parent).
        if (in_array($newParentId, $this->getAllNodeIds($node), true)) {
            return $tree;
        }

        $tree = $this->removeNode($tree, $nodeId);
        $tree = $this->addNode($tree, $newParentId, $node, $position);

        return $tree;
    }

    /**
     * Update properties of a specific node
     */
    public function updateNode(array $tree, string $nodeId, array $updates): array
    {
        if ($tree['id'] === $nodeId) {
            return array_merge($tree, $updates);
        }

        if (isset($tree['children'])) {
            foreach ($tree['children'] as $i => $child) {
                $tree['children'][$i] = $this->updateNode($child, $nodeId, $updates);
            }
        }

        return $tree;
    }

    /**
     * Find a node by ID in the tree
     */
    public function findNode(array $tree, string $nodeId): ?array
    {
        if ($tree['id'] === $nodeId) {
            return $tree;
        }

        if (isset($tree['children'])) {
            foreach ($tree['children'] as $child) {
                $found = $this->findNode($child, $nodeId);
                if ($found) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Get all node IDs in the tree (flat list)
     */
    public function getAllNodeIds(array $tree): array
    {
        $ids = [$tree['id']];

        if (isset($tree['children'])) {
            foreach ($tree['children'] as $child) {
                $ids = array_merge($ids, $this->getAllNodeIds($child));
            }
        }

        return $ids;
    }

    /**
     * Get default properties for a node type
     */
    public function getNodeDefaults(string $type): array
    {
        return match ($type) {
            // ---------------------------------------------------------------
            // Layout
            // ---------------------------------------------------------------
            'container' => [
                'type' => 'container',
                'tag' => 'div',
                'classes' => [],
                'styles' => ['default' => []],
                'children' => [],
            ],
            'section' => [
                'type' => 'container',
                'tag' => 'section',
                'classes' => [],
                'styles' => ['default' => ['padding' => '4rem 2rem']],
                'children' => [],
            ],
            'columns' => [
                'type' => 'columns',
                'tag' => 'div',
                'columnCount' => 2,
                'classes' => [],
                'styles' => ['default' => [
                    'display' => 'grid',
                    'gridTemplateColumns' => 'repeat(2, 1fr)',
                    'gap' => '1.5rem',
                ]],
                'children' => [
                    $this->createNode('column'),
                    $this->createNode('column'),
                ],
            ],
            'column' => [
                'type' => 'column',
                'tag' => 'div',
                'classes' => [],
                'styles' => ['default' => []],
                'children' => [],
            ],
            'divider' => [
                'type' => 'divider',
                'tag' => 'hr',
                'classes' => [],
                'styles' => ['default' => ['borderTop' => '1px solid #e5e7eb', 'margin' => '2rem 0']],
            ],
            'spacer' => [
                'type' => 'spacer',
                'tag' => 'div',
                'classes' => [],
                'styles' => ['default' => ['height' => '2rem']],
            ],

            // ---------------------------------------------------------------
            // Content
            // ---------------------------------------------------------------
            'heading' => [
                'type' => 'heading',
                'tag' => 'h2',
                'content' => ['type' => 'static', 'value' => 'Heading'],
                'classes' => [],
                'styles' => ['default' => ['fontSize' => '2rem']],
            ],
            'text' => [
                'type' => 'text',
                'tag' => 'p',
                'content' => ['type' => 'static', 'value' => 'Enter your text here.'],
                'classes' => [],
                'styles' => ['default' => []],
            ],
            'list' => [
                'type' => 'list',
                'tag' => 'ul',
                'classes' => [],
                'styles' => ['default' => ['paddingLeft' => '1.25rem']],
                'children' => [
                    $this->createNode('listitem'),
                    $this->createNode('listitem'),
                    $this->createNode('listitem'),
                ],
            ],
            'listitem' => [
                'type' => 'listitem',
                'tag' => 'li',
                'content' => ['type' => 'static', 'value' => 'List item'],
                'classes' => [],
                'styles' => ['default' => []],
            ],
            'link' => [
                'type' => 'link',
                'tag' => 'a',
                'content' => ['type' => 'static', 'value' => 'Link text'],
                'href' => '#',
                'classes' => [],
                'styles' => ['default' => []],
            ],
            'button' => [
                'type' => 'button',
                'tag' => 'a',
                'content' => ['type' => 'static', 'value' => 'Click Me'],
                'href' => '#',
                'classes' => [],
                'styles' => ['default' => [
                    'display' => 'inline-block',
                    'padding' => '0.75rem 1.5rem',
                    'backgroundColor' => 'var(--color-primary, #3b82f6)',
                    'color' => '#ffffff',
                    'borderRadius' => '0.375rem',
                    'textDecoration' => 'none',
                ]],
            ],
            'icon' => [
                'type' => 'icon',
                'tag' => 'span',
                'svg' => '<svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24"><path d="M12 2l2.9 6.6 7.1.6-5.4 4.7 1.6 7L12 17.8 5.8 21.5l1.6-7L2 9.8l7.1-.6L12 2z"/></svg>',
                'classes' => [],
                'styles' => ['default' => ['display' => 'inline-flex', 'width' => '1.5rem', 'height' => '1.5rem']],
            ],

            // ---------------------------------------------------------------
            // Media
            // ---------------------------------------------------------------
            'image' => [
                'type' => 'image',
                'tag' => 'img',
                'src' => ['type' => 'static', 'value' => ''],
                'alt' => '',
                'classes' => [],
                'styles' => ['default' => ['maxWidth' => '100%', 'height' => 'auto']],
            ],
            'video' => [
                'type' => 'video',
                'tag' => 'video',
                'src' => ['type' => 'static', 'value' => ''],
                'poster' => '',
                'controls' => true,
                'autoplay' => false,
                'loop' => false,
                'muted' => false,
                'classes' => [],
                'styles' => ['default' => ['maxWidth' => '100%', 'height' => 'auto']],
            ],

            // ---------------------------------------------------------------
            // Forms
            // ---------------------------------------------------------------
            'form' => [
                'type' => 'form',
                'tag' => 'form',
                'action' => '',
                'method' => 'post',
                'classes' => [],
                'styles' => ['default' => ['display' => 'flex', 'flexDirection' => 'column', 'gap' => '1rem']],
                'children' => [],
            ],
            'input' => [
                'type' => 'input',
                'tag' => 'input',
                'inputType' => 'text',
                'name' => '',
                'placeholder' => '',
                'required' => false,
                'classes' => [],
                'styles' => ['default' => [
                    'padding' => '0.5rem 0.75rem',
                    'border' => '1px solid #d1d5db',
                    'borderRadius' => '0.375rem',
                ]],
            ],
            'textarea' => [
                'type' => 'textarea',
                'tag' => 'textarea',
                'name' => '',
                'placeholder' => '',
                'rows' => 4,
                'required' => false,
                'classes' => [],
                'styles' => ['default' => [
                    'padding' => '0.5rem 0.75rem',
                    'border' => '1px solid #d1d5db',
                    'borderRadius' => '0.375rem',
                ]],
            ],
            'select' => [
                'type' => 'select',
                'tag' => 'select',
                'name' => '',
                'required' => false,
                'options' => [
                    ['value' => '', 'label' => 'Choose…'],
                ],
                'classes' => [],
                'styles' => ['default' => [
                    'padding' => '0.5rem 0.75rem',
                    'border' => '1px solid #d1d5db',
                    'borderRadius' => '0.375rem',
                ]],
            ],
            'label' => [
                'type' => 'label',
                'tag' => 'label',
                'content' => ['type' => 'static', 'value' => 'Label'],
                'for' => '',
                'classes' => [],
                'styles' => ['default' => ['fontWeight' => '600']],
            ],
            'freeform' => [
                'type' => 'freeform',
                'tag' => 'div',
                'handle' => '',
                'classes' => [],
                'styles' => ['default' => []],
            ],
            'formie' => [
                'type' => 'formie',
                'tag' => 'div',
                'handle' => '',
                'classes' => [],
                'styles' => ['default' => []],
            ],
            'submit' => [
                'type' => 'submit',
                'tag' => 'button',
                'content' => ['type' => 'static', 'value' => 'Submit'],
                'classes' => [],
                'styles' => ['default' => [
                    'display' => 'inline-block',
                    'padding' => '0.75rem 1.5rem',
                    'backgroundColor' => 'var(--color-primary, #3b82f6)',
                    'color' => '#ffffff',
                    'border' => 'none',
                    'borderRadius' => '0.375rem',
                    'cursor' => 'pointer',
                ]],
            ],

            // ---------------------------------------------------------------
            // Embed
            // ---------------------------------------------------------------
            'embed' => [
                'type' => 'embed',
                'tag' => 'iframe',
                'src' => '',
                'title' => 'Embedded content',
                'classes' => [],
                'styles' => ['default' => ['width' => '100%', 'aspectRatio' => '16 / 9', 'border' => '0']],
            ],
            'html' => [
                'type' => 'html',
                'tag' => 'div',
                'html' => '<!-- Custom HTML -->',
                'classes' => [],
                'styles' => ['default' => []],
            ],

            // ---------------------------------------------------------------
            // Data
            // ---------------------------------------------------------------
            'dynamic-list' => [
                'type' => 'dynamic-list',
                'tag' => 'div',
                'query' => 'craft.entries.section(\'\').limit(6)',
                'itemVar' => 'item',
                'classes' => [],
                'styles' => ['default' => ['display' => 'grid', 'gap' => '1.5rem']],
                'children' => [
                    $this->createNode('container'),
                ],
            ],

            // ---------------------------------------------------------------
            // Interactive
            // ---------------------------------------------------------------
            'card' => [
                'type' => 'card',
                'tag' => 'div',
                'classes' => [],
                'styles' => ['default' => [
                    'padding' => '1.5rem',
                    'background' => '#ffffff',
                    'border' => '1px solid #e5e7eb',
                    'borderRadius' => '0.5rem',
                    'boxShadow' => '0 1px 3px rgba(0,0,0,0.1)',
                ]],
                'children' => [
                    $this->createNode('heading'),
                    $this->createNode('text'),
                ],
            ],
            'alert' => [
                'type' => 'alert',
                'tag' => 'div',
                'dismissible' => true,
                'classes' => [],
                'styles' => ['default' => [
                    'padding' => '1rem 1.25rem',
                    'background' => '#eff6ff',
                    'border' => '1px solid #bfdbfe',
                    'borderRadius' => '0.375rem',
                    'color' => '#1e40af',
                ]],
                'children' => [
                    $this->createNode('text'),
                ],
            ],
            'counter' => [
                'type' => 'counter',
                'tag' => 'span',
                'end' => 100,
                'duration' => 2000,
                'prefix' => '',
                'suffix' => '',
                'classes' => [],
                'styles' => ['default' => ['fontSize' => '2.5rem', 'fontWeight' => '700']],
            ],
            'marquee' => [
                'type' => 'marquee',
                'tag' => 'div',
                'speed' => 20,
                'classes' => [],
                'styles' => ['default' => []],
                'children' => [
                    $this->createNode('text'),
                ],
            ],
            'tooltip' => [
                'type' => 'tooltip',
                'tag' => 'span',
                'text' => 'Tooltip text',
                'classes' => [],
                'styles' => ['default' => []],
                'children' => [
                    $this->createNode('text'),
                ],
            ],

            // ---------------------------------------------------------------
            // Interactive (composite runtime components)
            // ---------------------------------------------------------------
            'slideshow' => [
                'type' => 'slideshow',
                'tag' => 'div',
                'autoplay' => true,
                'interval' => 5000,
                'loop' => true,
                'showArrows' => true,
                'showDots' => true,
                'classes' => [],
                'styles' => ['default' => []],
                'children' => [
                    $this->createNode('slide'),
                    $this->createNode('slide'),
                ],
            ],
            'carousel' => [
                'type' => 'carousel',
                'tag' => 'div',
                'itemsPerView' => 3,
                'autoplay' => false,
                'interval' => 5000,
                'loop' => true,
                'showArrows' => true,
                'showDots' => false,
                'classes' => [],
                'styles' => ['default' => []],
                'children' => [
                    $this->createNode('slide'),
                    $this->createNode('slide'),
                    $this->createNode('slide'),
                ],
            ],
            'slide' => [
                'type' => 'slide',
                'tag' => 'div',
                'classes' => [],
                'styles' => ['default' => [
                    'padding' => '2rem',
                    'minHeight' => '200px',
                    'display' => 'flex',
                    'alignItems' => 'center',
                    'justifyContent' => 'center',
                    'background' => '#f3f4f6',
                ]],
                'children' => [],
            ],
            'popup' => [
                'type' => 'popup',
                'tag' => 'div',
                'trigger' => 'click',
                'triggerLabel' => 'Open Popup',
                'delay' => 3000,
                'classes' => [],
                'styles' => ['default' => []],
                'children' => [
                    $this->createNode('heading'),
                    $this->createNode('text'),
                ],
            ],
            'accordion' => [
                'type' => 'accordion',
                'tag' => 'div',
                'allowMultiple' => false,
                'classes' => [],
                'styles' => ['default' => []],
                'children' => [
                    $this->createNode('accordion-item'),
                    $this->createNode('accordion-item'),
                ],
            ],
            'accordion-item' => [
                'type' => 'accordion-item',
                'tag' => 'div',
                'title' => ['type' => 'static', 'value' => 'Section title'],
                'classes' => [],
                'styles' => ['default' => []],
                'children' => [
                    $this->createNode('text'),
                ],
            ],
            'tabs' => [
                'type' => 'tabs',
                'tag' => 'div',
                'classes' => [],
                'styles' => ['default' => []],
                'children' => [
                    $this->createNode('tab'),
                    $this->createNode('tab'),
                ],
            ],
            'tab' => [
                'type' => 'tab',
                'tag' => 'div',
                'label' => 'Tab',
                'classes' => [],
                'styles' => ['default' => []],
                'children' => [
                    $this->createNode('text'),
                ],
            ],

            default => $this->resolveCustomDefaults($type),
        };
    }

    /**
     * Defaults for a custom (registered) type, or a plain container fallback.
     */
    private function resolveCustomDefaults(string $type): array
    {
        $base = [
            'type' => $type,
            'tag' => 'div',
            'classes' => [],
            'styles' => ['default' => []],
            'children' => [],
        ];

        $definition = $this->componentTypes()?->get($type);
        if ($definition) {
            return array_merge($base, $definition->defaults, ['type' => $type]);
        }

        return $base;
    }

    /**
     * Get available atom types for the palette
     */
    public function getAtomPalette(): array
    {
        $palette = [
            // Layout
            ['type' => 'container', 'label' => 'Container', 'icon' => 'box', 'category' => 'Layout'],
            ['type' => 'section', 'label' => 'Section', 'icon' => 'layout', 'category' => 'Layout'],
            ['type' => 'columns', 'label' => 'Columns', 'icon' => 'columns', 'category' => 'Layout'],
            ['type' => 'divider', 'label' => 'Divider', 'icon' => 'minus', 'category' => 'Layout'],
            ['type' => 'spacer', 'label' => 'Spacer', 'icon' => 'maximize', 'category' => 'Layout'],

            // Content
            ['type' => 'heading', 'label' => 'Heading', 'icon' => 'type', 'category' => 'Content'],
            ['type' => 'text', 'label' => 'Text', 'icon' => 'align-left', 'category' => 'Content'],
            ['type' => 'list', 'label' => 'List', 'icon' => 'list', 'category' => 'Content'],
            ['type' => 'link', 'label' => 'Link', 'icon' => 'link', 'category' => 'Content'],
            ['type' => 'button', 'label' => 'Button', 'icon' => 'mouse-pointer', 'category' => 'Content'],
            ['type' => 'icon', 'label' => 'Icon', 'icon' => 'star', 'category' => 'Content'],

            // Media
            ['type' => 'image', 'label' => 'Image', 'icon' => 'image', 'category' => 'Media'],
            ['type' => 'video', 'label' => 'Video', 'icon' => 'video', 'category' => 'Media'],

            // Forms
            ['type' => 'form', 'label' => 'Form', 'icon' => 'clipboard', 'category' => 'Forms'],
            ['type' => 'input', 'label' => 'Input', 'icon' => 'edit', 'category' => 'Forms'],
            ['type' => 'textarea', 'label' => 'Textarea', 'icon' => 'edit-3', 'category' => 'Forms'],
            ['type' => 'select', 'label' => 'Select', 'icon' => 'chevron-down', 'category' => 'Forms'],
            ['type' => 'label', 'label' => 'Label', 'icon' => 'tag', 'category' => 'Forms'],
            ['type' => 'submit', 'label' => 'Submit', 'icon' => 'send', 'category' => 'Forms'],
            ['type' => 'freeform', 'label' => 'Freeform', 'icon' => 'file-text', 'category' => 'Forms'],
            ['type' => 'formie', 'label' => 'Formie', 'icon' => 'file-text', 'category' => 'Forms'],

            // Interactive
            ['type' => 'slideshow', 'label' => 'Slideshow', 'icon' => 'slideshow', 'category' => 'Interactive'],
            ['type' => 'carousel', 'label' => 'Carousel', 'icon' => 'carousel', 'category' => 'Interactive'],
            ['type' => 'popup', 'label' => 'Popup', 'icon' => 'popup', 'category' => 'Interactive'],
            ['type' => 'accordion', 'label' => 'Accordion', 'icon' => 'accordion', 'category' => 'Interactive'],
            ['type' => 'tabs', 'label' => 'Tabs', 'icon' => 'tabs', 'category' => 'Interactive'],
            ['type' => 'card', 'label' => 'Card', 'icon' => 'credit-card', 'category' => 'Interactive'],
            ['type' => 'alert', 'label' => 'Alert', 'icon' => 'alert-triangle', 'category' => 'Interactive'],
            ['type' => 'counter', 'label' => 'Counter', 'icon' => 'hash', 'category' => 'Interactive'],
            ['type' => 'marquee', 'label' => 'Marquee', 'icon' => 'chevrons-right', 'category' => 'Interactive'],
            ['type' => 'tooltip', 'label' => 'Tooltip', 'icon' => 'message-square', 'category' => 'Interactive'],

            // Data
            ['type' => 'dynamic-list', 'label' => 'Dynamic List', 'icon' => 'database', 'category' => 'Data'],

            // Embed
            ['type' => 'embed', 'label' => 'Embed', 'icon' => 'film', 'category' => 'Embed'],
            ['type' => 'html', 'label' => 'HTML', 'icon' => 'code', 'category' => 'Embed'],
        ];

        // Custom element types registered by other plugins/modules.
        $custom = $this->componentTypes()?->getPaletteItems() ?? [];

        return array_merge($palette, $custom);
    }

    /**
     * The custom-type registry, or null when Craft isn't booted (e.g. in unit
     * tests) — keeping the built-in tree logic usable in isolation.
     */
    private function componentTypes(): ?ComponentTypes
    {
        if (!class_exists('Yii', false) || \Yii::$app === null) {
            return null;
        }

        return \justinholtweb\rabbits\Plugin::getInstance()?->componentTypes;
    }

    /**
     * Create the default root node for a new component
     */
    public function createRootNode(): array
    {
        return [
            'id' => 'root',
            'type' => 'container',
            'tag' => 'div',
            'classes' => [],
            'styles' => ['default' => []],
            'children' => [],
        ];
    }
}
