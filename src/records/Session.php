<?php

namespace born05\twofactorauthentication\records;

use craft\db\ActiveRecord;

class Session extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%twofactorauthentication_session}}';
    }
}
