<?php

namespace born05\twofactorauthentication\controllers;

use Craft;
use craft\web\Controller;
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
        $request = Craft::$app->getRequest();

        $authenticationCode = $request->getPost('authenticationCode');

        // Get the current user
        $currentUser = Craft::$app->getUser()->getIdentity();

        if (TwoFactorAuth::$plugin->verify->verify($currentUser, $authenticationCode)) {
            $this->_handleSuccessfulLogin(true);
        } else {
            $errorCode = UserIdentity::ERROR_UNKNOWN_IDENTITY;
            $errorMessage = Craft::t('two-factor-authentication', 'Authentication code is invalid.');

            if ($request->getAcceptsJson()) {
                return $this->asJson(array(
                    'errorCode' => $errorCode,
                    'error' => $errorMessage
                ));
            } else {
                Craft::$app->user->setError($errorMessage);

                Craft::$app->urlManager->setRouteVariables(array(
                    'errorCode' => $errorCode,
                    'errorMessage' => $errorMessage,
                ));
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
        $currentUser = Craft::$app->getUser()->getIdentity();
        $request = Craft::$app->getRequest();

        // Were they trying to access a URL beforehand?
        $returnUrl = Craft::$app->user->getReturnUrl(null, true);

        // MODIFIED FROM COPY
        if ($returnUrl === null || $returnUrl == $request->getPath() || TwoFactorAuth::$plugin->response->isTwoFactorAuthenticationUrl($returnUrl)) {
            // If this is a CP request and they can access the control panel, send them wherever
            // postCpLoginRedirect tells us
            if ($request->isCpRequest() && $currentUser->can('accessCp')) {
                $postCpLoginRedirect = Craft::$app->getConfig()->get('postCpLoginRedirect');
                $returnUrl = UrlHelper::cpUrl($postCpLoginRedirect);
            } else {
                // Otherwise send them wherever postLoginRedirect tells us
                $postLoginRedirect = Craft::$app->getConfig()->get('postLoginRedirect');
                $returnUrl = UrlHelper::siteUrl($postLoginRedirect);
            }
        }

        // If this was an Ajax request, just return success:true
        if ($request->getAcceptsJson()) {
            $this->asJson(array(
                'success' => true,
                'returnUrl' => $returnUrl
            ));
        } else {
            if ($setNotice) {
                Craft::$app->user->setNotice(Craft::t('two-factor-authentication', 'Logged in.'));
            }

            $this->redirectToPostedUrl($currentUser, $returnUrl);
        }
    }
}
