<?php

namespace justinholtweb\rabbits\records;

use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property string $handle
 * @property string $componentType
 * @property string $componentStatus
 * @property string|null $tree
 * @property string|null $styles
 * @property string|null $animations
 * @property string|null $customCss
 * @property string|null $customJs
 * @property string|null $breakpoints
 * @property string|null $compiledTwig
 * @property string $uid
 */
class ComponentRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%rabbits_components}}';
    }
}
