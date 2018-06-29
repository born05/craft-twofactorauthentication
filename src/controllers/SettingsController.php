<?php

namespace born05\twofactorauthentication\controllers;

use Craft;
use craft\web\Controller;
use craft\elements\User;
use craft\helpers\UrlHelper;
use born05\twofactorauthentication\Plugin as TwoFactorAuth;

class SettingsController extends Controller
{
    /**
     * Turn on 2-factor for current user.
     */
    public function actionTurnOn() {
        $this->requirePostRequest();

        $user = Craft::$app->getUser()->getIdentity();
        $requestService = Craft::$app->getRequest();
        
        $authenticationCode = $requestService->getBodyParam('authenticationCode');
        $returnUrl = UrlHelper::cpUrl('two-factor-authentication');

        if (TwoFactorAuth::$plugin->verify->verify($user, $authenticationCode)) {
            if ($requestService->getAcceptsJson()) {
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

            if ($requestService->getAcceptsJson()) {
                return $this->asJson([
                    'errorCode' => $errorCode,
                    'error' => $errorMessage
                ]);
            } else {
                Craft::$app->user->setError($errorMessage);

                Craft::$app->urlManager->setRouteVariables([
                    'errorCode' => $errorCode,
                    'errorMessage' => $errorMessage,
                ]);
                return $this->redirect($returnUrl);
            }
        }
    }

    /**
     * Disable 2-factor for current user.
     */
    public function actionTurnOff() {
        $this->requirePostRequest();

        $user = Craft::$app->getUser()->getIdentity();
        TwoFactorAuth::$plugin->verify->disableUser($user);

        $returnUrl = UrlHelper::cpUrl('two-factor-authentication');
        return $this->redirect($returnUrl);
    }
}
