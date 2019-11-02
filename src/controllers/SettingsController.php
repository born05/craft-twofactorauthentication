<?php

namespace born05\twofactorauthentication\controllers;

use Craft;
use craft\web\Controller;
use craft\elements\User;
use craft\helpers\UrlHelper;
use born05\twofactorauthentication\Plugin as TwoFactorAuth;
use born05\twofactorauthentication\web\assets\verify\VerifyAsset;

class SettingsController extends Controller
{
    /**
     * Show the settings form.
     */
    public function actionIndex()
    {
        Craft::$app->view->registerAssetBundle(VerifyAsset::class);
        return $this->renderTemplate('two-factor-authentication/index');
    }
    
    /**
     * Show the settings form.
     */
    public function actionForce()
    {
        $user = Craft::$app->getUser()->getIdentity();

        if (TwoFactorAuth::$plugin->verify->isVerified($user)) {
            return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl());
        }

        Craft::$app->view->registerAssetBundle(VerifyAsset::class);
        return $this->renderTemplate('two-factor-authentication/_force');
    }

    /**
     * Turn on 2-factor for current user.
     */
    public function actionTurnOn()
    {
        $this->requirePostRequest();

        $user = Craft::$app->getUser()->getIdentity();
        $request = Craft::$app->getRequest();
  
        $authenticationCode = $request->getBodyParam('authenticationCode');

        if (TwoFactorAuth::$plugin->verify->verify($user, $authenticationCode)) {
            $returnUrl = TwoFactorAuth::$plugin->response->getReturnUrl();
            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => true,
                    'returnUrl' => $returnUrl
                ]);
            } else {
                return $this->redirect($returnUrl);
            }
        } else {
            $errorCode = User::AUTH_INVALID_CREDENTIALS;
            $errorMessage = Craft::t('two-factor-authentication', 'Authentication code is invalid.');

            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'errorCode' => $errorCode,
                    'error' => $errorMessage
                ]);
            } else {
                Craft::$app->getSession()->setError($errorMessage);

                Craft::$app->getUrlManager()->setRouteParams([
                    'errorCode' => $errorCode,
                    'errorMessage' => $errorMessage,
                ]);
                $returnUrl = TwoFactorAuth::$plugin->response->getReturnUrl();
                if (Craft::$app->getRequest()->getIsCpRequest() === false) {
                    $settings = TwoFactorAuth::$plugin->getSettings();
                    $returnUrl = $settings->getSettingsPath();
                }
                return $this->redirect($returnUrl);
            }
        }
    }

    /**
     * Disable 2-factor for current user.
     */
    public function actionTurnOff()
    {
        $this->requirePostRequest();

        $user = Craft::$app->getUser()->getIdentity();
        TwoFactorAuth::$plugin->verify->disableUser($user);

        if (Craft::$app->getRequest()->getIsCpRequest() && Craft::$app->getUser()->checkPermission('accessCp')) {
            $returnUrl = UrlHelper::cpUrl('two-factor-authentication');
        }else{
            $returnUrl = TwoFactorAuth::$plugin->response->getReturnUrl();
        }
        return $this->redirect($returnUrl);
    }
}
