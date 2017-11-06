<?php

namespace born05\twofactorauthentication\records;

use craft\db\ActiveRecord;

class Session extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%twofactorauthentication_session}}';
    }

    protected function defineAttributes()
    {
        return array(
            'dateVerified' => array(AttributeType::DateTime),
        );
    }

    public function defineRelations()
    {
        return array(
            'user' => array(static::BELONGS_TO, 'UserRecord', 'required' => true, 'onDelete' => static::CASCADE),
            'session' => array(static::BELONGS_TO, 'SessionRecord', 'required' => true, 'onDelete' => static::CASCADE),
        );
    }

    public function defineIndexes()
    {
        return array(
            array('columns' => array('userId', 'sessionId'), 'unique' => true),
        );
    }
}