<?php

namespace born05\twofactorauthentication\records;

use craft\db\ActiveRecord;

/**
 * Class User record.
 *
 * @property int $id ID
 * @property int $userId User ID
 * @property string $secret Secret
 * @property string|null $dateVerified Date verified
 * @property string $dateCreated Date created
 * @property string $dateUpdated Date modified
 */
class User extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%twofactorauthentication_user}}';
    }
}
