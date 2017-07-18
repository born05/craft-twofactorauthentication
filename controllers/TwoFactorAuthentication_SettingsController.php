<?php

namespace Craft;

class TwoFactorAuthentication_SettingsController extends BaseController
{
    /**
     * Turn on 2-factor for current user.
     */
    public function actionTurnOn() {
        $this->requirePostRequest();

        $user = craft()->userSession->getUser();
        $authenticationCode = craft()->request->getPost('authenticationCode');
        $returnUrl = UrlHelper::getCpUrl('twofactorauthentication');

        if (craft()->twoFactorAuthentication_verify->verify($user, $authenticationCode)) {
            if (craft()->request->isAjaxRequest()) {
                $this->returnJson(array(
                    'success' => true,
                    'returnUrl' => $returnUrl
                ));
            } else {
                $this->redirect($returnUrl);
            }
        } else {
            $errorCode = UserIdentity::ERROR_UNKNOWN_IDENTITY;
            $errorMessage = Craft::t('Authentication code is invalid.');

            if (craft()->request->isAjaxRequest()) {
                $this->returnJson(array(
                    'errorCode' => $errorCode,
                    'error' => $errorMessage
                ));
            } else {
                craft()->userSession->setError($errorMessage);

                craft()->urlManager->setRouteVariables(array(
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

        $user = craft()->userSession->getUser();
        craft()->twoFactorAuthentication_verify->disableUser($user);

        $returnUrl = UrlHelper::getCpUrl('twofactorauthentication');
        $this->redirect($returnUrl);
    }
}
