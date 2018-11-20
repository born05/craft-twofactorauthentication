<?php

namespace born05\twofactorauthentication;

use Craft;
use born05\twofactorauthentication\Plugin as TwoFactorAuth;

class Variables
{
    /**
     * Determines if the user is verified.
     * @param  User $user
     * @return boolean
     */
    public function isUserVerified()
    {
        $user = Craft::$app->getUser()->getIdentity();
        return TwoFactorAuth::$plugin->verify->isVerified($user);
    }

    public function getCurrentUserSecret()
    {
        $user = Craft::$app->getUser()->getIdentity();

        $rawSecret = TwoFactorAuth::$plugin->verify->getUserSecret($user);
        return str_split($rawSecret, 4);
    }

    public function getCurrentUserQRCode()
    {
        $user = Craft::$app->getUser()->getIdentity();
        return TwoFactorAuth::$plugin->verify->getUserQRCode($user);
    }
}
