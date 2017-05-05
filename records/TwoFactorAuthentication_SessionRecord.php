<?php

namespace Craft;

class TwoFactorAuthentication_SessionRecord extends BaseRecord
{
    public function getTableName()
    {
        return 'twofactorauthentication_sessions';
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