<?php

namespace justinholtweb\rabbits\records;

use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property string $category
 * @property string $handle
 * @property string $label
 * @property string $value
 * @property string $uid
 */
class TokenRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%rabbits_tokens}}';
    }
}
