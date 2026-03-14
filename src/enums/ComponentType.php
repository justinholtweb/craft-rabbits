<?php

namespace justinholtweb\rabbits\enums;

enum ComponentType: string
{
    case Atom = 'atom';
    case Molecule = 'molecule';
    case Organism = 'organism';
    case Template = 'template';

    public function label(): string
    {
        return match ($this) {
            self::Atom => 'Atom',
            self::Molecule => 'Molecule',
            self::Organism => 'Organism',
            self::Template => 'Template',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Atom => '#3b82f6',
            self::Molecule => '#8b5cf6',
            self::Organism => '#f59e0b',
            self::Template => '#10b981',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Atom => 'Basic building blocks: text, images, buttons',
            self::Molecule => 'Groups of atoms: cards, heroes, CTAs',
            self::Organism => 'Complex sections: headers, footers, grids',
            self::Template => 'Full page layouts composed of organisms',
        };
    }
}
