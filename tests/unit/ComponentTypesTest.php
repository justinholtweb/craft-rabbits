<?php

namespace justinholtweb\rabbits\tests\unit;

use justinholtweb\rabbits\events\RegisterComponentTypesEvent;
use justinholtweb\rabbits\models\NodeType;
use justinholtweb\rabbits\services\ComponentTypes;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the custom component-type registry — the public extension
 * point developers use to add their own element types via the
 * EVENT_REGISTER_COMPONENT_TYPES event.
 */
final class ComponentTypesTest extends TestCase
{
    private function registryWith(NodeType ...$types): ComponentTypes
    {
        $registry = new ComponentTypes();
        $registry->on(
            ComponentTypes::EVENT_REGISTER_COMPONENT_TYPES,
            function (RegisterComponentTypesEvent $event) use ($types) {
                foreach ($types as $type) {
                    $event->types[] = $type;
                }
            }
        );
        return $registry;
    }

    public function testEmptyByDefault(): void
    {
        $this->assertSame([], (new ComponentTypes())->getAll());
    }

    public function testRegistersTypeViaEvent(): void
    {
        $registry = $this->registryWith(new NodeType(['type' => 'rating', 'label' => 'Star Rating']));

        $this->assertTrue($registry->has('rating'));
        $this->assertInstanceOf(NodeType::class, $registry->get('rating'));
        $this->assertFalse($registry->has('nope'));
        $this->assertNull($registry->get('nope'));
    }

    public function testPaletteItemShape(): void
    {
        $registry = $this->registryWith(
            new NodeType(['type' => 'rating', 'label' => 'Star Rating', 'icon' => 'star', 'category' => 'Custom'])
        );

        $this->assertSame(
            [['type' => 'rating', 'label' => 'Star Rating', 'icon' => 'star', 'category' => 'Custom']],
            $registry->getPaletteItems()
        );
    }

    public function testPaletteItemFallsBackToDefaultIconAndCategory(): void
    {
        $registry = $this->registryWith(new NodeType(['type' => 'thing', 'label' => 'Thing']));
        $item = $registry->getPaletteItems()[0];

        $this->assertSame('box', $item['icon']);
        $this->assertSame('Custom', $item['category']);
    }

    public function testClientDefinitionsExposeFieldsButNotRenderCallable(): void
    {
        $type = new NodeType([
            'type' => 'rating',
            'label' => 'Star Rating',
            'fields' => [['name' => 'stars', 'label' => 'Stars', 'type' => 'number']],
            'render' => fn() => 'x',
        ]);

        $defs = $this->registryWith($type)->getClientDefinitions();

        $this->assertArrayHasKey('rating', $defs);
        $this->assertArrayNotHasKey('render', $defs['rating']);
        $this->assertSame([['name' => 'stars', 'label' => 'Stars', 'type' => 'number']], $defs['rating']['fields']);
    }

    public function testIgnoresEntriesWithoutAType(): void
    {
        $registry = $this->registryWith(new NodeType(['type' => '', 'label' => 'No Type']));
        $this->assertSame([], $registry->getAll());
    }

    public function testLaterRegistrationOfSameTypeWins(): void
    {
        $registry = $this->registryWith(
            new NodeType(['type' => 'rating', 'label' => 'First']),
            new NodeType(['type' => 'rating', 'label' => 'Second']),
        );

        $this->assertSame('Second', $registry->get('rating')->label);
    }
}
