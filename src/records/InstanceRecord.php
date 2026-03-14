<?php

namespace justinholtweb\rabbits\records;

use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property int $componentId
 * @property int|null $entryId
 * @property int $sortOrder
 * @property string|null $overrides
 * @property string $uid
 */
class InstanceRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%rabbits_instances}}';
    }
}
