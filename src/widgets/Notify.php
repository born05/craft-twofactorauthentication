<?php
namespace born05\twofactorauth\widgets;

use craft\base\Widget;

class Notify extends Widget
{
    public function displayName()
    {
        return \Craft::t('app', 'Two-factor authentication status');
    }

    public function getBodyHtml()
    {
        $user = \Craft::$app->user->getUser();

        return \Craft::$app->getView()->renderTemplate('twofactorauthentication/_widgets/status/body', [
            'isEnabled' => \Craft::$app->twoFactorAuthentication_verify->isEnabled($user),
        ]);
    }
}
