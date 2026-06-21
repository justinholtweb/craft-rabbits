<?php

namespace justinholtweb\rabbits\tests\unit;

use justinholtweb\rabbits\enums\AnimationTrigger;
use justinholtweb\rabbits\enums\ComponentStatus;
use justinholtweb\rabbits\enums\ComponentType;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the backed string enums.
 *
 * These guard the contract the rest of the plugin relies on: the stored string
 * values (persisted in the database and in component trees) and the
 * label()/color() mappings rendered by the element index and CP templates. A
 * renamed case value would silently break existing rows and validation ranges,
 * so the exact string values are pinned here.
 */
final class EnumsTest extends TestCase
{
    // ---- ComponentType ------------------------------------------------------

    public function testComponentTypeValues(): void
    {
        // Pinned by Component::defineRules() validation range and defineSources().
        $values = array_map(fn(ComponentType $t) => $t->value, ComponentType::cases());
        $this->assertSame(['atom', 'molecule', 'organism', 'template'], $values);
    }

    public function testComponentTypeLabels(): void
    {
        $this->assertSame('Atom', ComponentType::Atom->label());
        $this->assertSame('Molecule', ComponentType::Molecule->label());
        $this->assertSame('Organism', ComponentType::Organism->label());
        $this->assertSame('Template', ComponentType::Template->label());
    }

    public function testComponentTypeColorsAreHex(): void
    {
        foreach (ComponentType::cases() as $type) {
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $type->color());
        }
    }

    public function testComponentTypeDescriptionsArePresent(): void
    {
        foreach (ComponentType::cases() as $type) {
            $this->assertNotSame('', $type->description());
        }
    }

    public function testComponentTypeFromStoredValueRoundTrips(): void
    {
        foreach (ComponentType::cases() as $case) {
            $this->assertSame($case, ComponentType::from($case->value));
        }
    }

    public function testComponentTypeRejectsUnknownValue(): void
    {
        $this->assertNull(ComponentType::tryFrom('widget'));
    }

    // ---- ComponentStatus ----------------------------------------------------

    public function testComponentStatusValues(): void
    {
        $this->assertSame('draft', ComponentStatus::Draft->value);
        $this->assertSame('active', ComponentStatus::Active->value);
        $this->assertSame('archived', ComponentStatus::Archived->value);
    }

    public function testComponentStatusLabels(): void
    {
        $this->assertSame('Draft', ComponentStatus::Draft->label());
        $this->assertSame('Active', ComponentStatus::Active->label());
        $this->assertSame('Archived', ComponentStatus::Archived->label());
    }

    public function testComponentStatusColors(): void
    {
        // Map to Craft status badge classes.
        $this->assertSame('orange', ComponentStatus::Draft->color());
        $this->assertSame('green', ComponentStatus::Active->color());
        $this->assertSame('red', ComponentStatus::Archived->color());
    }

    public function testComponentStatusFromStoredValueRoundTrips(): void
    {
        foreach (ComponentStatus::cases() as $case) {
            $this->assertSame($case, ComponentStatus::from($case->value));
        }
    }

    // ---- AnimationTrigger ---------------------------------------------------

    public function testAnimationTriggerValuesMatchRuntimeSelectors(): void
    {
        // These exact strings are emitted as data-rabbits-animate="<value>" and
        // matched by the frontend runtime selectors — keep them in lockstep.
        $values = array_map(fn(AnimationTrigger $t) => $t->value, AnimationTrigger::cases());
        $this->assertSame(['click', 'hover', 'scroll-into-view', 'page-load'], $values);
    }

    public function testAnimationTriggerLabels(): void
    {
        $this->assertSame('On Click', AnimationTrigger::Click->label());
        $this->assertSame('On Hover', AnimationTrigger::Hover->label());
        $this->assertSame('Scroll Into View', AnimationTrigger::Scroll->label());
        $this->assertSame('Page Load', AnimationTrigger::Load->label());
    }

    public function testAnimationTriggerRejectsUnknownValue(): void
    {
        $this->assertNull(AnimationTrigger::tryFrom('on-click'));
    }
}
