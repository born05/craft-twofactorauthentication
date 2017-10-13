<?php

namespace Craft;

class TwoFactorAuthentication_UserRecord extends BaseRecord
{
    public function getTableName()
    {
        return 'twofactorauthentication_users';
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