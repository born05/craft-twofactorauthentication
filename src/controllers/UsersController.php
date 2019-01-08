<?php

namespace born05\twofactorauthentication\controllers;

use Craft;
use craft\web\Controller;
use craft\elements\User;
use craft\helpers\UrlHelper;
use born05\twofactorauthentication\Plugin as TwoFactorAuth;
use born05\twofactorauthentication\web\assets\verify\VerifyAsset;

class UsersController extends Controller
{
    /**
     * Disable 2-factor for the provided user.
     */
    public function actionTurnOff()
    {
        $this->requirePostRequest();

        if (Craft::$app->getUser()->getIsAdmin()) {
            $userId = Craft::$app->getRequest()->getRequiredBodyParam('userId');
            $user = Craft::$app->getUsers()->getUserById($userId);

            if ($user) {
                TwoFactorAuth::$plugin->verify->disableUser($user);
            }
        }
    }
}
