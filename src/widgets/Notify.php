<?php
namespace born05\twofactorauthentication\widgets;

use Craft;
use craft\base\Widget;
use born05\twofactorauthentication\Plugin as TwoFactorAuth;

class Notify extends Widget
{
    public static function displayName()
    {
        return Craft::t('app', 'Two-factor authentication status');
    }

    public function getBodyHtml()
    {
        $user = Craft::$app->user->getUser();

        return Craft::$app->getView()->renderTemplate('twofactorauthentication/_widgets/status/body', [
            'isEnabled' => \TwoFactorAuth::$plugin->verify->isEnabled($user),
        ]);
    }
}
