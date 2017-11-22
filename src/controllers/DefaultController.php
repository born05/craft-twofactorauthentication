<?php

namespace born05\twofactorauthentication\controllers;

use Craft;
use craft\web\Controller;
use born05\twofactorauthentication\Plugin as TwoFactorAuth;

class DefaultController extends Controller
{
    /**
     * Show the verify form.
     */
    public function actionIndex()
    {
        $user = Craft::$app->getUser()->getIdentity();

        $rawSecret = TwofactorAuthentication::$plugin->verify->getUserSecret($user);

        return $this->renderCPTemplate('twofactorauthentication/index', [
            'isUserVerified' => TwofactorAuthentication::$plugin->verify->isVerified($user),
            'currentUserSecret' => str_split($rawSecret, 4),
            'currentUserQRCode' => TwofactorAuthentication::$plugin->verify->getUserQRCode($user),
        ]);
    }
}
