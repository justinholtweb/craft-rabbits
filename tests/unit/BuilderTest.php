<?php

namespace justinholtweb\rabbits\tests\unit;

use justinholtweb\rabbits\services\Builder;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the tree-manipulation service that backs the visual builder.
 *
 * These are pure array transforms (no database, no booted Craft app), so they
 * pin the exact behaviour the builder API depends on — including the move
 * guard that prevents a node from being dropped into its own subtree.
 */
final class BuilderTest extends TestCase
{
    private Builder $builder;

    protected function setUp(): void
    {
        $this->builder = new Builder();
    }

    /**
     * A small, hand-built tree:
     *
     *   root
     *   ├── a (heading)
     *   └── b (container)
     *       └── c (text)
     */
    private function tree(): array
    {
        return [
            'id' => 'root',
            'type' => 'container',
            'children' => [
                ['id' => 'a', 'type' => 'heading'],
                [
                    'id' => 'b',
                    'type' => 'container',
                    'children' => [
                        ['id' => 'c', 'type' => 'text'],
                    ],
                ],
            ],
        ];
    }

    // ---- findNode -----------------------------------------------------------

    public function testFindNodeReturnsNestedNode(): void
    {
        $found = $this->builder->findNode($this->tree(), 'c');
        $this->assertSame('text', $found['type']);
    }

    public function testFindNodeReturnsNullWhenMissing(): void
    {
        $this->assertNull($this->builder->findNode($this->tree(), 'nope'));
    }

    public function testGetAllNodeIdsCollectsEveryId(): void
    {
        $ids = $this->builder->getAllNodeIds($this->tree());
        $this->assertSame(['root', 'a', 'b', 'c'], $ids);
    }

    // ---- addNode ------------------------------------------------------------

    public function testAddNodeAppendsChildToParent(): void
    {
        $tree = $this->builder->addNode($this->tree(), 'b', ['id' => 'x', 'type' => 'text']);
        $b = $this->builder->findNode($tree, 'b');
        $this->assertCount(2, $b['children']);
        $this->assertSame('x', $b['children'][1]['id']);
    }

    public function testAddNodeRespectsPosition(): void
    {
        $tree = $this->builder->addNode($this->tree(), 'root', ['id' => 'x', 'type' => 'text'], 0);
        $this->assertSame('x', $tree['children'][0]['id']);
        $this->assertSame('a', $tree['children'][1]['id']);
    }

    // ---- removeNode ---------------------------------------------------------

    public function testRemoveNodeRemovesSubtree(): void
    {
        $tree = $this->builder->removeNode($this->tree(), 'b');
        $this->assertSame(['root', 'a'], $this->builder->getAllNodeIds($tree));
        $this->assertNull($this->builder->findNode($tree, 'c'));
    }

    // ---- updateNode ---------------------------------------------------------

    public function testUpdateNodeMergesUpdatesIntoMatchingNode(): void
    {
        $tree = $this->builder->updateNode($this->tree(), 'a', ['tag' => 'h1', 'classes' => ['lead']]);
        $a = $this->builder->findNode($tree, 'a');
        $this->assertSame('h1', $a['tag']);
        $this->assertSame(['lead'], $a['classes']);
        $this->assertSame('heading', $a['type']); // untouched keys preserved
    }

    // ---- moveNode -----------------------------------------------------------

    public function testMoveNodeRelocatesNode(): void
    {
        $tree = $this->builder->moveNode($this->tree(), 'a', 'b', 0);
        $this->assertNull($this->builder->findNode($tree, 'a')['children'] ?? null);
        $b = $this->builder->findNode($tree, 'b');
        $ids = array_map(fn($c) => $c['id'], $b['children']);
        $this->assertSame(['a', 'c'], $ids);
        // 'a' is no longer a direct child of root
        $rootChildIds = array_map(fn($c) => $c['id'], $tree['children']);
        $this->assertSame(['b'], $rootChildIds);
    }

    public function testMoveNodeIntoOwnDescendantIsANoOp(): void
    {
        // Regression: moving 'b' into its descendant 'c' would orphan the
        // subtree (remove succeeds, re-add can't find the new parent).
        $original = $this->tree();
        $result = $this->builder->moveNode($original, 'b', 'c', 0);
        $this->assertSame($original, $result);
    }

    public function testMoveNodeIntoItselfIsANoOp(): void
    {
        $original = $this->tree();
        $this->assertSame($original, $this->builder->moveNode($original, 'b', 'b', 0));
    }

    // ---- createNode / defaults ---------------------------------------------

    public function testCreateNodeAssignsUniquePrefixedId(): void
    {
        $node = $this->builder->createNode('heading');
        $this->assertStringStartsWith('node_', $node['id']);
        $this->assertSame('heading', $node['type']);

        $other = $this->builder->createNode('heading');
        $this->assertNotSame($node['id'], $other['id']);
    }

    public function testCreateNodeAppliesOverrides(): void
    {
        $node = $this->builder->createNode('heading', ['tag' => 'h3']);
        $this->assertSame('h3', $node['tag']);
    }

    public function testGetNodeDefaultsUnknownTypeFallsBackToContainer(): void
    {
        $defaults = $this->builder->getNodeDefaults('totally-unknown');
        $this->assertSame('totally-unknown', $defaults['type']);
        $this->assertSame('div', $defaults['tag']);
        $this->assertArrayHasKey('children', $defaults);
    }

    public function testCreateRootNodeIsAContainerWithRootId(): void
    {
        $root = $this->builder->createRootNode();
        $this->assertSame('root', $root['id']);
        $this->assertSame('container', $root['type']);
        $this->assertSame([], $root['children']);
    }

    // ---- palette ------------------------------------------------------------

    public function testAtomPaletteEntriesAreWellFormed(): void
    {
        $palette = $this->builder->getAtomPalette();
        $this->assertNotEmpty($palette);
        foreach ($palette as $atom) {
            $this->assertArrayHasKey('type', $atom);
            $this->assertArrayHasKey('label', $atom);
            $this->assertArrayHasKey('icon', $atom);
            $this->assertArrayHasKey('category', $atom);
        }
    }

    public function testAtomPaletteCoversKeyElementTypes(): void
    {
        $types = array_column($this->builder->getAtomPalette(), 'type');
        foreach (['container', 'heading', 'image', 'slideshow', 'dynamic-list', 'formie'] as $expected) {
            $this->assertContains($expected, $types);
        }
    }
}
