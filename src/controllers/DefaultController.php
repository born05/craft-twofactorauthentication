<?php

namespace born05\twofactorauthentication\controllers;

use Craft;
use craft\web\Controller;
use born05\twofactorauthentication\Plugin as TwoFactorAuth;
use born05\twofactorauthentication\web\assets\verify\VerifyAsset;

class DefaultController extends Controller
{
    /**
     * Show the verify form.
     */
    public function actionIndex()
    {
        $user = Craft::$app->getUser()->getIdentity();

        $rawSecret = TwoFactorAuth::$plugin->verify->getUserSecret($user);
        
        Craft::$app->view->registerAssetBundle(VerifyAsset::class);
        return $this->renderTemplate('two-factor-authentication/index', [
            'isUserVerified' => TwoFactorAuth::$plugin->verify->isVerified($user),
            'currentUserSecret' => str_split($rawSecret, 4),
            'currentUserQRCode' => TwoFactorAuth::$plugin->verify->getUserQRCode($user),
        ]);
    }
}
