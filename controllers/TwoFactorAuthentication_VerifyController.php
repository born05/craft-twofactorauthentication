<?php

namespace Craft;

class TwoFactorAuthentication_VerifyController extends BaseController
{
    /**
     * Show the verify form.
     */
    public function actionLogin()
    {
        $this->renderCPTemplate('twofactorauthentication/_verify');
    }

    /**
     * Handle the verify post.
     */
    public function actionLoginProcess()
    {
        $this->requirePostRequest();

        $authenticationCode = craft()->request->getPost('authenticationCode');

        // Get the current user
        $currentUser = craft()->userSession->getUser();

        if (craft()->twoFactorAuthentication_verify->verify($currentUser, $authenticationCode)) {
            $this->_handleSuccessfulLogin(true);
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
        craft()->templates->setTemplateMode(TemplateMode::CP);
        $this->renderTemplate($path, array(
            'CraftEdition'  => craft()->getEdition(),
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
        $currentUser = craft()->userSession->getUser();

        // Were they trying to access a URL beforehand?
        $returnUrl = craft()->userSession->getReturnUrl(null, true);

        // MODIFIED FROM COPY
        if ($returnUrl === null || $returnUrl == craft()->request->getPath() || craft()->twoFactorAuthentication_response->isTwoFactorAuthenticationUrl($returnUrl)) {
            // If this is a CP request and they can access the control panel, send them wherever
            // postCpLoginRedirect tells us
            if (craft()->request->isCpRequest() && $currentUser->can('accessCp')) {
                $postCpLoginRedirect = craft()->config->get('postCpLoginRedirect');
                $returnUrl = UrlHelper::getCpUrl($postCpLoginRedirect);
            } else {
                // Otherwise send them wherever postLoginRedirect tells us
                $postLoginRedirect = craft()->config->get('postLoginRedirect');
                $returnUrl = UrlHelper::getSiteUrl($postLoginRedirect);
            }
        }

        // If this was an Ajax request, just return success:true
        if (craft()->request->isAjaxRequest()) {
            $this->returnJson(array(
                'success' => true,
                'returnUrl' => $returnUrl
            ));
        } else {
            if ($setNotice) {
                craft()->userSession->setNotice(Craft::t('Logged in.'));
            }

            $this->redirectToPostedUrl($currentUser, $returnUrl);
        }
    }
}
