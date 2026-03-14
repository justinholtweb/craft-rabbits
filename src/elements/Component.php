<?php

namespace justinholtweb\rabbits\elements;

use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Html;
use craft\helpers\Json;
use justinholtweb\rabbits\elements\db\ComponentQuery;
use justinholtweb\rabbits\enums\ComponentStatus;
use justinholtweb\rabbits\enums\ComponentType;
use justinholtweb\rabbits\records\ComponentRecord;
use yii\base\InvalidConfigException;

class Component extends Element
{
    public ?string $handle = null;
    public string $componentType = 'atom';
    public string $componentStatus = 'draft';
    public array|string|null $tree = null;
    public array|string|null $styles = null;
    public array|string|null $animations = null;
    public ?string $customCss = null;
    public ?string $customJs = null;
    public array|string|null $breakpoints = null;
    public ?string $compiledTwig = null;

    public static function displayName(): string
    {
        return Craft::t('rabbits', 'Component');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('rabbits', 'Components');
    }

    public static function lowerDisplayName(): string
    {
        return Craft::t('rabbits', 'component');
    }

    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('rabbits', 'components');
    }

    public static function refHandle(): ?string
    {
        return 'rabbitscomponent';
    }

    public static function hasTitles(): bool
    {
        return true;
    }

    public static function hasContent(): bool
    {
        return true;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function statuses(): array
    {
        return [
            ComponentStatus::Draft->value => [
                'label' => ComponentStatus::Draft->label(),
                'color' => ComponentStatus::Draft->color(),
            ],
            ComponentStatus::Active->value => [
                'label' => ComponentStatus::Active->label(),
                'color' => ComponentStatus::Active->color(),
            ],
            ComponentStatus::Archived->value => [
                'label' => ComponentStatus::Archived->label(),
                'color' => ComponentStatus::Archived->color(),
            ],
        ];
    }

    public function getStatus(): ?string
    {
        return $this->componentStatus;
    }

    public static function find(): ComponentQuery
    {
        return new ComponentQuery(static::class);
    }

    public static function defineSources(string $context = null): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('rabbits', 'All Components'),
            ],
        ];

        foreach (ComponentType::cases() as $type) {
            $sources[] = [
                'key' => 'type:' . $type->value,
                'label' => $type->label() . 's',
                'criteria' => ['componentType' => $type->value],
            ];
        }

        return $sources;
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'handle' => Craft::t('rabbits', 'Handle'),
            'componentType' => Craft::t('rabbits', 'Type'),
            'componentStatus' => Craft::t('rabbits', 'Status'),
            'dateCreated' => Craft::t('app', 'Date Created'),
            'dateUpdated' => Craft::t('app', 'Date Updated'),
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return ['title', 'handle', 'componentType', 'componentStatus', 'dateUpdated'];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'handle' => Craft::t('rabbits', 'Handle'),
            'componentType' => Craft::t('rabbits', 'Type'),
            [
                'label' => Craft::t('app', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
        ];
    }

    protected static function defineActions(string $source = null): array
    {
        return [
            Delete::class,
        ];
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['handle', 'componentType'];
    }

    protected function tableAttributeHtml(string $attribute): string
    {
        return match ($attribute) {
            'componentType' => Html::tag('span', $this->getComponentType()->label(), [
                'style' => 'color: ' . $this->getComponentType()->color(),
            ]),
            'componentStatus' => Html::tag('span', $this->getComponentStatus()->label(), [
                'class' => 'status ' . $this->getComponentStatus()->color(),
            ]),
            default => parent::tableAttributeHtml($attribute),
        };
    }

    public function getComponentType(): ComponentType
    {
        return ComponentType::from($this->componentType);
    }

    public function getComponentStatus(): ComponentStatus
    {
        return ComponentStatus::from($this->componentStatus);
    }

    public function getTreeArray(): array
    {
        if (is_string($this->tree)) {
            return Json::decodeIfJson($this->tree) ?: [];
        }
        return $this->tree ?? [];
    }

    public function getStylesArray(): array
    {
        if (is_string($this->styles)) {
            return Json::decodeIfJson($this->styles) ?: [];
        }
        return $this->styles ?? [];
    }

    public function getAnimationsArray(): array
    {
        if (is_string($this->animations)) {
            return Json::decodeIfJson($this->animations) ?: [];
        }
        return $this->animations ?? [];
    }

    public function getBreakpointsArray(): array
    {
        if (is_string($this->breakpoints)) {
            return Json::decodeIfJson($this->breakpoints) ?: [];
        }
        return $this->breakpoints ?? [];
    }

    public function getCpEditUrl(): ?string
    {
        return 'rabbits/components/' . $this->id;
    }

    public function afterSave(bool $isNew): void
    {
        if (!$isNew) {
            $record = ComponentRecord::findOne($this->id);

            if (!$record) {
                throw new InvalidConfigException('Invalid component ID: ' . $this->id);
            }
        } else {
            $record = new ComponentRecord();
            $record->id = $this->id;
        }

        $record->handle = $this->handle;
        $record->componentType = $this->componentType;
        $record->componentStatus = $this->componentStatus;
        $record->tree = is_array($this->tree) ? Json::encode($this->tree) : $this->tree;
        $record->styles = is_array($this->styles) ? Json::encode($this->styles) : $this->styles;
        $record->animations = is_array($this->animations) ? Json::encode($this->animations) : $this->animations;
        $record->customCss = $this->customCss;
        $record->customJs = $this->customJs;
        $record->breakpoints = is_array($this->breakpoints) ? Json::encode($this->breakpoints) : $this->breakpoints;
        $record->compiledTwig = $this->compiledTwig;

        $record->save(false);

        parent::afterSave($isNew);
    }

    public function afterDelete(): void
    {
        parent::afterDelete();
    }

    public function canView(\craft\elements\User $user): bool
    {
        return $user->can('rabbits:accessPlugin');
    }

    public function canSave(\craft\elements\User $user): bool
    {
        return $user->can('rabbits:manageComponents');
    }

    public function canDelete(\craft\elements\User $user): bool
    {
        return $user->can('rabbits:manageComponents');
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['handle'], 'required'];
        $rules[] = [['handle'], 'string', 'max' => 255];
        $rules[] = [['handle'], 'match', 'pattern' => '/^[a-zA-Z][a-zA-Z0-9_-]*$/'];
        $rules[] = [['componentType'], 'in', 'range' => array_column(ComponentType::cases(), 'value')];
        $rules[] = [['componentStatus'], 'in', 'range' => array_column(ComponentStatus::cases(), 'value')];

        return $rules;
    }
}
