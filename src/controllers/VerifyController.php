<?php

namespace born05\twofactorauthentication\controllers;

use Craft;
use craft\web\Controller;
use craft\elements\User;
use craft\helpers\UrlHelper;
use born05\twofactorauthentication\Plugin as TwoFactorAuth;
use born05\twofactorauthentication\web\assets\verify\VerifyAsset;

class VerifyController extends Controller
{
    /**
     * Show the verify form.
     */
    public function actionLogin()
    {
        Craft::$app->view->registerAssetBundle(VerifyAsset::class);
        return $this->renderTemplate('two-factor-authentication/_verify');
    }

    /**
     * Handle the verify post.
     */
    public function actionLoginProcess()
    {
        $this->requirePostRequest();
        $requestService = Craft::$app->getRequest();

        $authenticationCode = $requestService->getBodyParam('authenticationCode');

        // Get the current user
        $currentUser = Craft::$app->getUser()->getIdentity();

        if (TwoFactorAuth::$plugin->verify->verify($currentUser, $authenticationCode)) {
            return $this->_handleSuccessfulLogin(true);
        } else {
            $errorCode = User::AUTH_INVALID_CREDENTIALS;
            $errorMessage = Craft::t('two-factor-authentication', 'Authentication code is invalid.');

            if ($requestService->getAcceptsJson()) {
                return $this->asJson([
                    'errorCode' => $errorCode,
                    'error' => $errorMessage
                ]);
            } else {
                Craft::$app->getUser()->setError($errorMessage);

                Craft::$app->urlManager->setRouteVariables([
                    'errorCode' => $errorCode,
                    'errorMessage' => $errorMessage,
                ]);
            }
        }
    }

    /**
     * COPIED from https://github.com/pixelandtonic/Craft-Release/blob/master/app/controllers/UsersController.php
     *
     * Redirects the user after a successful login attempt, or if they visited the Login page while they were already
     * logged in.
     *
     * @param bool $setNotice Whether a flash notice should be set, if this isn't an Ajax request.
     *
     * @return null
     */
    private function _handleSuccessfulLogin($setNotice)
    {
        // Get the current user
        $userService = Craft::$app->getUser();
        $requestService = Craft::$app->getRequest();
        $currentUser = $userService->getIdentity();
        $returnUrl = $userService->getReturnUrl();

        // MODIFIED FROM COPY
        // Prevent looping back to the verify controller.
        if ($returnUrl === null || $returnUrl == $requestService->getPath() || TwoFactorAuth::$plugin->response->isTwoFactorAuthenticationUrl($returnUrl)) {
            // If this is a CP request and they can access the control panel, send them wherever
            // postCpLoginRedirect tells us
            if ($requestService->isCpRequest() && $currentUser->can('accessCp')) {
                $postCpLoginRedirect = Craft::$app->getConfig()->get('postCpLoginRedirect');
                $returnUrl = UrlHelper::cpUrl($postCpLoginRedirect);
            } else {
                // Otherwise send them wherever postLoginRedirect tells us
                $postLoginRedirect = Craft::$app->getConfig()->get('postLoginRedirect');
                $returnUrl = UrlHelper::siteUrl($postLoginRedirect);
            }
        }

        // If this was an Ajax request, just return success:true
        if ($requestService->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'returnUrl' => $returnUrl
            ]);
        } else {
            if ($setNotice) {
                $userService->setNotice(Craft::t('two-factor-authentication', 'Logged in.'));
            }

            return $this->redirectToPostedUrl($currentUser, $returnUrl);
        }
    }
}
