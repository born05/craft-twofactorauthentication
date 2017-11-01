<?php

namespace born05\twofactorauth\controllers;

use craft\web\Controller;

class SettingsController extends Controller
{
    /**
     * Turn on 2-factor for current user.
     */
    public function actionTurnOn() {
        $this->requirePostRequest();

        $user = \Craft::$app->user->getUser();
        $authenticationCode = \Craft::$app->request->getPost('authenticationCode');
        $returnUrl = UrlHelper::getCpUrl('twofactorauthentication');

        if (Craft::$app->twoFactorAuthentication_verify->verify($user, $authenticationCode)) {
            if (Craft::$app->request->isAjaxRequest()) {
                $this->returnJson(array(
                    'success' => true,
                    'returnUrl' => $returnUrl
                ));
            } else {
                $this->redirect($returnUrl);
            }
        } else {
            $errorCode = UserIdentity::ERROR_UNKNOWN_IDENTITY;
            $errorMessage = \Craft::t('app', 'Authentication code is invalid.');

            if (Craft::$app->request->isAjaxRequest()) {
                $this->returnJson(array(
                    'errorCode' => $errorCode,
                    'error' => $errorMessage
                ));
            } else {
                \Craft::$app->user->setError($errorMessage);

                \Craft::$app->urlManager->setRouteVariables(array(
                    'errorCode' => $errorCode,
                    'errorMessage' => $errorMessage,
                ));
                $this->redirect($returnUrl);
            }
        }
    }

    /**
     * Disable 2-factor for current user.
     */
    public function actionTurnOff() {
        $this->requirePostRequest();

        $user = \Craft::$app->user->getUser();
        \Craft::$app->twoFactorAuthentication_verify->disableUser($user);

        $returnUrl = UrlHelper::getCpUrl('twofactorauthentication');
        $this->redirect($returnUrl);
    }
}
