<?php

namespace born05\twofactorauthentication\migrations;

use born05\twofactorauthentication\migrations\Install;

class m180720_143000_craft3_upgrade extends Install
{
    public function safeUp()
    {
        $this->upgradeFromCraft2();

        return true;
    }

    public function safeDown()
    {
        return false;
    }
}
