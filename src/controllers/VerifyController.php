<?php

namespace born05\twofactorauthentication\controllers;

use Craft;
use craft\web\Controller;
use born05\twofactorauthentication\Plugin as TwoFactorAuth;

class VerifyController extends Controller
{
    /**
     * Show the verify form.
     */
    public function actionLogin()
    {
        return $this->renderCPTemplate('twofactorauthentication/_verify');
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

        if (TwofactorAuthentication::$plugin->verify->verify($currentUser, $authenticationCode)) {
            $this->_handleSuccessfulLogin(true);
        } else {
            $errorCode = UserIdentity::ERROR_UNKNOWN_IDENTITY;
            $errorMessage = Craft::t('app', 'Authentication code is invalid.');

            if ($request->isAjaxRequest()) {
                return $this->returnJson(array(
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
     * Render a template in admin modus
     * @param  string $path
     * @return void
     */
    private function renderCPTemplate($path)
    {
        Craft::$app->templates->setTemplateMode(TemplateMode::CP);
        $this->renderTemplate($path, array(
            'CraftEdition'  => Craft::$app->getEdition(),
            'CraftPersonal' => Craft::Personal,
            'CraftClient'   => Craft::Client,
            'CraftPro'      => Craft::Pro,
        ));
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
        if ($returnUrl === null || $returnUrl == $request->getPath() || TwofactorAuthentication::$plugin->response->isTwoFactorAuthenticationUrl($returnUrl)) {
            // If this is a CP request and they can access the control panel, send them wherever
            // postCpLoginRedirect tells us
            if ($request->isCpRequest() && $currentUser->can('accessCp')) {
                $postCpLoginRedirect = Craft::$app->config->get('postCpLoginRedirect');
                $returnUrl = UrlHelper::getCpUrl($postCpLoginRedirect);
            } else {
                // Otherwise send them wherever postLoginRedirect tells us
                $postLoginRedirect = Craft::$app->config->get('postLoginRedirect');
                $returnUrl = UrlHelper::getSiteUrl($postLoginRedirect);
            }
        }

        // If this was an Ajax request, just return success:true
        if ($request->isAjaxRequest()) {
            $this->returnJson(array(
                'success' => true,
                'returnUrl' => $returnUrl
            ));
        } else {
            if ($setNotice) {
                Craft::$app->user->setNotice(Craft::t('app', 'Logged in.'));
            }

            $this->redirectToPostedUrl($currentUser, $returnUrl);
        }
    }
}
