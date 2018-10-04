<?php
namespace born05\twofactorauthentication\services;

use Craft;
use born05\twofactorauthentication\Plugin as TwoFactorAuth;

use craft\elements\User;
use craft\helpers\UrlHelper;
use yii\base\Component;
use yii\web\UserEvent;

class Request extends Component
{
    /**
     * Only allow users who are verified or don't use two-factor.
     */
    public function validateRequest()
    {
        $request = Craft::$app->getRequest();
        $response = Craft::$app->getResponse();
        $verify = TwoFactorAuth::$plugin->verify;

        if ($this->shouldRedirectCp() || $this->shouldRedirectFrontEnd()) {
            // Get the current user
            $user = Craft::$app->getUser()->getIdentity();

            // Only redirect two-factor enabled users who aren't verified yet.
            if (isset($user) &&
                (
                    $verify->isEnabled($user) ||
                    ($this->isForced() && !$this->is2FASettingsRequest())
                ) &&
                !$verify->isVerified($user)
            ) {
                Craft::$app->getUser()->logout(false);

                if ($request->getIsCpRequest()) {
                    $response->redirect(UrlHelper::cpUrl());
                } else {
                    $response->redirect(UrlHelper::siteUrl());
                }
            }
        }
    }
    
    public function userLoginEventHandler(UserEvent $event)
    {
        // Don't redirect cookieBased events.
        if ($event->cookieBased) {
            return;
        }

        $user = Craft::$app->getUser()->getIdentity();
        $request = Craft::$app->getRequest();
        $response = Craft::$app->getResponse();
        $responseService = TwoFactorAuth::$plugin->response;
        $verify = TwoFactorAuth::$plugin->verify;

        if (isset($user) &&
            $verify->isEnabled($user) &&
            !$verify->isVerified($user)
        ) {
            if ($request->getIsCpRequest()) {
                $url = UrlHelper::actionUrl('two-factor-authentication/verify/login');
            } else {
                $url = UrlHelper::siteUrl(TwoFactorAuth::$plugin->getSettings()->getVerifyPath());
            }

            // Redirect to verification page.
            if ($request->getAcceptsJson()) {
                return Craft::$app->end(0, $responseService->asJson([
                    'success' => true,
                    'returnUrl' => $url
                ]));
            } else {
                return Craft::$app->end(0, $response->redirect($url));
            }
        } else if (isset($user) &&
            !$verify->isEnabled($user) &&
            $this->isForced()
        ) {
            $url = $this->getSettingsUrl();

            // Redirect to verification page.
            if ($request->getAcceptsJson()) {
                return Craft::$app->end(0, $responseService->asJson([
                    'success' => true,
                    'returnUrl' => $url
                ]));
            } else {
                return Craft::$app->end(0, $response->redirect($url));
            }
        }
    }

    /**
     * Test CP requests.
     * @return boolean
     */
    private function shouldRedirectCp()
    {
        $request = Craft::$app->getRequest();
        $actionSegs = $request->getActionSegments();

        return $request->getIsCpRequest() &&
               !$this->isCraftSpecialRequests() &&
               !$this->is2FASpecialRequests();
    }

    /**
     * Test front end requests.
     * @return boolean
     */
    private function shouldRedirectFrontEnd()
    {
        $request = Craft::$app->getRequest();
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $settings = TwoFactorAuth::$plugin->getSettings();

        // Frontend requests only.
        if (!$settings->verifyFrontEnd || $request->getIsCpRequest()) {
            return false;
        }

        $frontEndPathWhitelist = $settings->getFrontEndPathWhitelist();
        $frontEndPathBlacklist = $settings->getFrontEndPathBlacklist();
        $pathInfo = $request->getPathInfo();

        $isLoginPath = (
            $pathInfo === trim($generalConfig->getLoginPath(), '/') ||
            $pathInfo === trim($generalConfig->getLogoutPath(), '/') ||
            $pathInfo === trim($settings->getVerifyPath(), '/') ||
            $pathInfo === trim($settings->getSettingsPath(), '/')
        );

        $isWhitelisted = (
            empty($frontEndPathWhitelist) ||
            in_array($pathInfo, $frontEndPathWhitelist)
        );

        $isBlacklisted = (
            empty($frontEndPathBlacklist) ||
            in_array($pathInfo, $frontEndPathBlacklist)
        );

        return !$this->isCraftSpecialRequests() &&
            !$this->is2FASpecialRequests() &&
            !$isLoginPath &&
            !$isWhitelisted &&
            $isBlacklisted;
    }

    /**
     * Test Craft special requests.
     * @return boolean
     */
    private function isCraftSpecialRequests()
    {
        $request = Craft::$app->getRequest();
        $actionSegs = $request->getActionSegments();

        return (
            // COPIED from craft\web\Application::_isSpecialCaseActionRequest
            $request->getPathInfo() === '' ||
            $actionSegs === ['app', 'migrate'] ||
            $actionSegs === ['users', 'login'] ||
            $actionSegs === ['users', 'logout'] ||
            $actionSegs === ['users', 'set-password'] ||
            $actionSegs === ['users', 'verify-email'] ||
            $actionSegs === ['users', 'forgot-password'] ||
            $actionSegs === ['users', 'send-password-reset-email'] ||
            $actionSegs === ['users', 'save-user'] ||
            $actionSegs === ['users', 'get-remaining-session-time'] ||
            $actionSegs[0] === 'updater' ||
            $actionSegs[0] === 'debug'
        );
    }

    /**
     * Test 2FA special requests.
     * @return boolean
     */
    public function is2FASpecialRequests()
    {
        $request = Craft::$app->getRequest();
        $actionSegs = $request->getActionSegments();

        return (
            $actionSegs === ['two-factor-authentication', 'verify'] ||
            $this->is2FASettingsRequest()
        );
    }

    /**
     * Test 2FA settings request.
     * @return boolean
     */
    public function is2FASettingsRequest()
    {
        $request = Craft::$app->getRequest();
        $actionSegs = $request->getActionSegments();

        return (
            $request->getAbsoluteUrl() === $this->getSettingsUrl() ||
            $actionSegs === ['two-factor-authentication', 'settings', 'turn-on']
        );
    }

    /**
     * Determine if 2FA is forced.
     * @return boolean
     */
    private function isForced()
    {
        $request = Craft::$app->getRequest();

        if ($request->getIsCpRequest()) {
            return TwoFactorAuth::$plugin->getSettings()->forceBackEnd;
        }

        return TwoFactorAuth::$plugin->getSettings()->forceFrontEnd;
    }

    /**
     * Determine if 2FA is forced.
     * @return string
     */
    private function getSettingsUrl()
    {
        $request = Craft::$app->getRequest();

        if ($request->getIsCpRequest()) {
            return UrlHelper::actionUrl('two-factor-authentication/settings/force');
        }

        return UrlHelper::siteUrl(TwoFactorAuth::$plugin->getSettings()->getSettingsPath());
    }
}
