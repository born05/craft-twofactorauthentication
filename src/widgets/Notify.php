<?php
namespace born05\twofactorauthentication\widgets;

use Craft;
use craft\base\Widget;
use born05\twofactorauthentication\Plugin as TwoFactorAuth;

class Notify extends Widget
{
    public static function displayName(): string
    {
        return Craft::t('two-factor-authentication', 'Two-factor authentication status');
    }
    
    public static function iconPath()
    {
        return Craft::getAlias('@born05/twofactorauthentication/icon-mask.svg');
    }

    public function getBodyHtml()
    {
        $user = Craft::$app->getUser()->getIdentity();

        return Craft::$app->view->renderTemplate('two-factor-authentication/_widgets/status/body', [
            'isEnabled' => TwoFactorAuth::$plugin->verify->isEnabled($user),
        ]);
    }
}
