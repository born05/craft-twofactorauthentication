<?php
namespace born05\twofactorauthentication\migrations;

use craft\db\Migration;
use craft\db\Query;
use born05\twofactorauthentication\records\Session;
use born05\twofactorauthentication\records\User;

class Install extends Migration
{
    public function safeUp()
    {
        if ($this->_upgradeFromCraft2()) {
            return;
        }
        
        // Fresh install code goes here...
        $this->createTable(Session::tableName(), [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'sessionId' => $this->integer()->notNull(),
            'dateVerified' => $this->dateTime()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        
        $this->createTable(User::tableName(), [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'secret' => $this->string(512)->notNull(),
            'dateVerified' => $this->dateTime()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        
        $this->createIndex(null, Session::tableName(), ['userId', 'sessionId'], true);
        $this->createIndex(null, User::tableName(), ['userId'], true);
        
        $this->addForeignKey(null, Session::tableName(), ['userId'], '{{%users}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Session::tableName(), ['sessionId'], '{{%sessions}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, User::tableName(), ['userId'], '{{%users}}', ['id'], 'CASCADE', null);
    }

    private function _upgradeFromCraft2()
    {
        // Fetch the old plugin row, if it was installed
        $row = (new Query())
            ->select(['id', 'settings'])
            ->from(['{{%plugins}}'])
            ->where(['in', 'handle', ['twofactorauthentication']])
            ->one();
        
        if (!$row) {
            return false;
        }

        // Update this one's settings to old values
        $this->update('{{%plugins}}', [
            'settings' => $row['settings']
        ], ['handle' => 'two-factor-authentication']);

        // Delete the old row
        $this->delete('{{%plugins}}', ['id' => $row['id']]);

        // Any additional upgrade code goes here...

        return true;
    }

    public function safeDown()
    {
        $this->dropTableIfExists(Session::tableName());
        $this->dropTableIfExists(User::tableName());

        return true;
    }
}