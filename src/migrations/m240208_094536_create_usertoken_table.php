<?php

namespace born05\twofactorauthentication\migrations;

use craft\db\Migration;
use born05\twofactorauthentication\records\UserToken;

class m240208_094536_create_usertoken_table extends Migration
{
    public function safeUp()
    {
        $this->createTable(UserToken::tableName(), [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'token' => $this->string(100)->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, UserToken::tableName(), ['userId', 'dateCreated'], false);
        $this->createIndex(null, UserToken::tableName(), ['userId', 'token', 'dateCreated'], true);

        return true;
    }

    public function safeDown()
    {
        $this->dropTableIfExists(UserToken::tableName());

        return false;
    }
}
