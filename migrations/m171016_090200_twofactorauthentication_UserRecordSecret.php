<?php
namespace Craft;

class m171016_090200_twofactorauthentication_UserRecordSecret extends BaseMigration
{
    public function safeUp()
    {
        craft()->db->createCommand()->alterColumn('twofactorauthentication_users', 'secret', [AttributeType::String, 'maxLength' => 512, 'required' => true]);

        return true;
    }
}
