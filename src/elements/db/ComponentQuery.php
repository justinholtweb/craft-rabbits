<?php

namespace justinholtweb\rabbits\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use justinholtweb\rabbits\enums\ComponentStatus;
use justinholtweb\rabbits\enums\ComponentType;

class ComponentQuery extends ElementQuery
{
    public ?string $handle = null;
    public ?string $componentType = null;
    public ?string $componentStatus = null;

    public function handle(?string $value): self
    {
        $this->handle = $value;
        return $this;
    }

    public function componentType(string|ComponentType|null $value): self
    {
        $this->componentType = $value instanceof ComponentType ? $value->value : $value;
        return $this;
    }

    public function componentStatus(string|ComponentStatus|null $value): self
    {
        $this->componentStatus = $value instanceof ComponentStatus ? $value->value : $value;
        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('rabbits_components');

        $this->query->select([
            'rabbits_components.handle',
            'rabbits_components.componentType',
            'rabbits_components.componentStatus',
            'rabbits_components.tree',
            'rabbits_components.styles',
            'rabbits_components.animations',
            'rabbits_components.customCss',
            'rabbits_components.customJs',
            'rabbits_components.breakpoints',
            'rabbits_components.compiledTwig',
        ]);

        if ($this->handle !== null) {
            $this->subQuery->andWhere(Db::parseParam('rabbits_components.handle', $this->handle));
        }

        if ($this->componentType !== null) {
            $this->subQuery->andWhere(Db::parseParam('rabbits_components.componentType', $this->componentType));
        }

        if ($this->componentStatus !== null) {
            $this->subQuery->andWhere(Db::parseParam('rabbits_components.componentStatus', $this->componentStatus));
        }

        return parent::beforePrepare();
    }

    protected function statusCondition(string $status): mixed
    {
        return match ($status) {
            ComponentStatus::Draft->value => ['rabbits_components.componentStatus' => 'draft'],
            ComponentStatus::Active->value => ['rabbits_components.componentStatus' => 'active'],
            ComponentStatus::Archived->value => ['rabbits_components.componentStatus' => 'archived'],
            default => parent::statusCondition($status),
        };
    }
}
