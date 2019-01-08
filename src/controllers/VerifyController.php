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
        $this->requireLogin();
        Craft::$app->view->registerAssetBundle(VerifyAsset::class);
        return $this->renderTemplate('two-factor-authentication/_verify');
    }

    /**
     * Handle the verify post.
     */
    public function actionLoginProcess()
    {
        $this->requireLogin();
        $this->requirePostRequest();
        $responseService = TwoFactorAuth::$plugin->response;
        $request = Craft::$app->getRequest();

        $authenticationCode = $request->getBodyParam('authenticationCode');

        // Get the current user
        $user = Craft::$app->getUser()->getIdentity();

        if (TwoFactorAuth::$plugin->verify->verify($user, $authenticationCode)) {
            return $this->_handleSuccessfulLogin(true);
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
            }
        }
    }

    /**
     * Redirects the user to the login template if they're not logged in.
     */
    public function requireLogin()
    {
        $user = Craft::$app->getUser();

        if ($user->getIsGuestWithoutVerification()) {
            $user->loginRequired();
            Craft::$app->end();
        }
    }

    /**
     * COPIED from \craft\controllers\UsersController::_handleSuccessfulLogin
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
        $request = Craft::$app->getRequest();
        $responseService = TwoFactorAuth::$plugin->response;
        $returnUrl = $responseService->getReturnUrl();

        // If this was an Ajax request, just return success:true
        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'returnUrl' => $returnUrl
            ]);
        }
        
        if ($setNotice) {
            Craft::$app->getSession()->setNotice(Craft::t('two-factor-authentication', 'Logged in.'));
        }

        $user = Craft::$app->getUser()->getIdentity();

        return $this->redirectToPostedUrl($user, $returnUrl);
    }
}
