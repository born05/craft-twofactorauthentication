<?php

namespace born05\twofactorauthentication\records;

use craft\db\ActiveRecord;

/**
 * Class UserToken record.
 *
 * @property int $id ID
 * @property int $userId User ID
 * @property string $token Token
 * @property string $dateCreated Date created
 * @property string $dateUpdated Date modified
 */
class UserToken extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%twofactorauthentication_usertoken}}';
    }
}
