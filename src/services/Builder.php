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
            'image' => [
                'type' => 'image',
                'tag' => 'img',
                'src' => ['type' => 'static', 'value' => ''],
                'alt' => '',
                'classes' => [],
                'styles' => ['default' => ['maxWidth' => '100%', 'height' => 'auto']],
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
            'link' => [
                'type' => 'link',
                'tag' => 'a',
                'content' => ['type' => 'static', 'value' => 'Link text'],
                'href' => '#',
                'classes' => [],
                'styles' => ['default' => []],
            ],
            default => [
                'type' => $type,
                'tag' => 'div',
                'classes' => [],
                'styles' => ['default' => []],
                'children' => [],
            ],
        };
    }

    /**
     * Get available atom types for the palette
     */
    public function getAtomPalette(): array
    {
        return [
            ['type' => 'container', 'label' => 'Container', 'icon' => 'box'],
            ['type' => 'section', 'label' => 'Section', 'icon' => 'layout'],
            ['type' => 'heading', 'label' => 'Heading', 'icon' => 'type'],
            ['type' => 'text', 'label' => 'Text', 'icon' => 'align-left'],
            ['type' => 'image', 'label' => 'Image', 'icon' => 'image'],
            ['type' => 'button', 'label' => 'Button', 'icon' => 'mouse-pointer'],
            ['type' => 'divider', 'label' => 'Divider', 'icon' => 'minus'],
            ['type' => 'spacer', 'label' => 'Spacer', 'icon' => 'maximize'],
            ['type' => 'link', 'label' => 'Link', 'icon' => 'link'],
        ];
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
