<?php

namespace born05\twofactorauthentication\controllers;

use Craft;
use craft\web\Controller;
use craft\web\View;
use craft\web\User;
use craft\helpers\UrlHelper;
use born05\twofactorauthentication\Plugin as TwoFactorAuth;
use born05\twofactorauthentication\web\assets\verify\VerifyAsset;
use yii\web\ForbiddenHttpException;

class SettingsController extends Controller
{
    /**
     * Show the settings form.
     */
    public function actionIndex()
    {
        Craft::$app->getView()->registerAssetBundle(VerifyAsset::class);
        return $this->renderTemplate('two-factor-authentication/index', [], View::TEMPLATE_MODE_CP);
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

        Craft::$app->getView()->registerAssetBundle(VerifyAsset::class);
        return $this->renderTemplate('two-factor-authentication/_force', [], View::TEMPLATE_MODE_CP);
    }

    /**
     * Turn on 2-factor for current user.
     */
    public function actionTurnOn()
    {
        $this->requirePostRequest();

        /** @var User */
        $userSession = Craft::$app->getUser();
        $user = $userSession->getIdentity();
        $request = Craft::$app->getRequest();

        $authenticationCode = $request->getBodyParam('authenticationCode');

        if (TwoFactorAuth::$plugin->verify->verify($user, $authenticationCode)) {
            $returnUrl = TwoFactorAuth::$plugin->response->getReturnUrl();

            // If this was an Ajax request, just return success:true
            if ($this->request->getAcceptsJson()) {
                $return = [
                    'returnUrl' => $returnUrl,
                ];

                if (Craft::$app->getConfig()->getGeneral()->enableCsrfProtection) {
                    $return['csrfTokenValue'] = $this->request->getCsrfToken();
                }

                return $this->asSuccess(data: $return);
            }

            return $this->redirectToPostedUrl($userSession->getIdentity(), $returnUrl);
        } else {
            $errorCode = \craft\elements\User::AUTH_INVALID_CREDENTIALS;
            $errorMessage = Craft::t('two-factor-authentication', 'Authentication code is invalid.');

            return $this->asFailure(
                $errorMessage,
                data: [
                    'errorCode' => $errorCode,
                ],
                routeParams: [
                    'errorCode' => $errorCode,
                    'errorMessage' => $errorMessage,
                ]
            );
        }
    }

    /**
     * Disable 2-factor for current user.
     */
    public function actionTurnOff()
    {
        $this->requirePostRequest();

        $user = Craft::$app->getUser()->getIdentity();

        if (!TwoFactorAuth::$plugin->verify->isVerified($user)) {
            throw new ForbiddenHttpException('User is not permitted to perform this action.');
        }

        TwoFactorAuth::$plugin->verify->disableUser($user);

        if (Craft::$app->getRequest()->getIsCpRequest() && Craft::$app->getUser()->checkPermission('accessCp')) {
            $returnUrl = UrlHelper::cpUrl('two-factor-authentication');
        } else {
            $returnUrl = TwoFactorAuth::$plugin->response->getReturnUrl();
        }
        return $this->redirect($returnUrl);
    }
}
