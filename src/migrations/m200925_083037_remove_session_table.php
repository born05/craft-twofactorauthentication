<?php

namespace born05\twofactorauthentication\migrations;

use Craft;
use craft\db\Migration;

class m200925_083037_remove_session_table extends Migration
{
    public function safeUp()
    {
        $this->dropTableIfExists('{{%twofactorauthentication_session}}');

        return true;
    }

    public function safeDown()
    {
        return false;
    }
}
