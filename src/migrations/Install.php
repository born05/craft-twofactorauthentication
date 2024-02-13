<?php
namespace born05\twofactorauthentication\migrations;

use craft\db\Migration;
use born05\twofactorauthentication\records\User;
use born05\twofactorauthentication\records\UserToken;

class Install extends Migration
{
    public function safeUp()
    {
        if ($this->upgradeFromCraft2()) {
            return;
        }

        $this->createTable(User::tableName(), [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'secret' => $this->string(512)->notNull(),
            'dateVerified' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, User::tableName(), ['userId'], true);
        $this->addForeignKey(null, User::tableName(), ['userId'], '{{%users}}', ['id'], 'CASCADE', null);

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
    }

    protected function upgradeFromCraft2()
    {
        // Fetch the old plugin row, if it was installed
        $oldTableExists = $this->db->schema->getTableSchema('{{%twofactorauthentication_sessions}}', true);
        if (!isset($oldTableExists)) {
            return false;
        }

        // Any additional upgrade code goes here...
        $this->dropTableIfExists('{{%twofactorauthentication_sessions}}');
        $this->renameTable('{{%twofactorauthentication_users}}', User::tableName());

        return true;
    }

    public function safeDown()
    {
        $this->dropTableIfExists('{{%twofactorauthentication_session}}');
        $this->dropTableIfExists(User::tableName());
        $this->dropTableIfExists(UserToken::tableName());

        return true;
    }
}
