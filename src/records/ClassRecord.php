<?php

namespace justinholtweb\rabbits\records;

use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property string $handle
 * @property string $name
 * @property string|null $styles
 * @property string|null $breakpoints
 * @property string $uid
 */
class ClassRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%rabbits_classes}}';
    }
}
