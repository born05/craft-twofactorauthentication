<?php

namespace born05\twofactorauthentication\records;

use craft\db\ActiveRecord;

class User extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%twofactorauthentication_user}}';
    }

    protected function defineAttributes()
    {
        return array(
            'secret' => array(AttributeType::String, 'maxLength' => 512, 'required' => true),
            'dateVerified' => array(AttributeType::DateTime),
        );
    }

    public function defineRelations()
    {
        return array(
            'user' => array(static::BELONGS_TO, 'UserRecord', 'required' => true, 'onDelete' => static::CASCADE),
        );
    }

    public function defineIndexes()
    {
        return array(
            array('columns' => array('userId'), 'unique' => true),
        );
    }
}