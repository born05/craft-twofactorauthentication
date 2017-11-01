<?php

namespace born05\twofactorauth\controllers;

use craft\web\Controller;

class DefaultController extends Controller
{
    /**
     * Show the verify form.
     */
    public function actionIndex()
    {
        $user = \Craft::$app->user->getUser();

        $rawSecret = \Craft::$app->twoFactorAuthentication_verify->getUserSecret($user);

        return $this->renderCPTemplate('twofactorauthentication/index', [
            'isUserVerified' => \Craft::$app->twoFactorAuthentication_verify->isVerified($user),
            'currentUserSecret' => str_split($rawSecret, 4),
            'currentUserQRCode' => \Craft::$app->twoFactorAuthentication_verify->getUserQRCode($user),
        ]);
    }
}
