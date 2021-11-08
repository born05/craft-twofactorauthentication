<?php
namespace born05\twofactorauthentication\services;

use Craft;
use born05\twofactorauthentication\Plugin as TwoFactorAuth;

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

        $settings = TwoFactorAuth::$plugin->getSettings();

        // Don't verify when disabled.
        if ($request->getIsCpRequest()) {
            if (!$settings->verifyBackEnd) {
                return;
            }
        } elseif (!$settings->verifyFrontEnd) {
            return;
        }

        if (isset($user) &&
            $verify->isEnabled($user) &&
            !$verify->isVerified($user)
        ) {
            if ($request->getIsCpRequest()) {
                $url = UrlHelper::actionUrl('two-factor-authentication/verify/login');
            } else {
                $url = UrlHelper::siteUrl($settings->verifyPath);
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
        } elseif (isset($user) &&
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
        $settings = TwoFactorAuth::$plugin->getSettings();

        $pathInfo = $request->getPathInfo();

        $isAllowed = false;
        foreach ($settings->backEndPathAllow as $path) {
            if ($this->isRegex("/$path/i")) {
                if (preg_match("/$path/i", $pathInfo)) {
                    $isAllowed = true;
                }
            } elseif ($path === $pathInfo) {
                $isAllowed = true;
            }
        }

        return ($settings->verifyBackEnd && $request->getIsCpRequest()) &&
                // COPIED from craft\web\Application::_isSpecialCaseActionRequest
                $request->getPathInfo() !== '' &&
                !$isAllowed &&
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

        $pathInfo = $request->getPathInfo();

        $isLoginPath = (
            $pathInfo === trim($generalConfig->getLoginPath(), '/') ||
            $pathInfo === trim($generalConfig->getLogoutPath(), '/') ||
            $pathInfo === trim($settings->verifyPath, '/') ||
            $pathInfo === trim($settings->settingsPath, '/')
        );

        $isAllowed = false;
        foreach ($settings->frontEndPathAllow as $path) {
            if ($this->isRegex("/$path/i")) {
                if (preg_match("/$path/i", $pathInfo)) {
                    $isAllowed = true;
                }
            } elseif ($path === $pathInfo) {
                $isAllowed = true;
            }
        }

        $isExcluded = false;
        foreach ($settings->frontEndPathExclude as $path) {
            if ($this->isRegex("/$path/i")) {
                if (preg_match("/$path/i", $pathInfo)) {
                    $isExcluded = true;
                }
            } elseif ($path === $pathInfo) {
                $isExcluded = true;
            }
        }

        return !$this->isCraftSpecialRequests() &&
            !$this->is2FASpecialRequests() &&
            !$isLoginPath &&
            !$isAllowed &&
            $isExcluded;
    }

    /**
     * Test Craft special requests.
     * @return boolean
     */
    private function isCraftSpecialRequests()
    {
        // COPIED from craft\web\Application::_isSpecialCaseActionRequest
        $request = Craft::$app->getRequest();
        $actionSegs = $request->getActionSegments();

        if (empty($actionSegs)) {
            return false;
        }

        return (
            $actionSegs === ['app', 'migrate'] ||
            $actionSegs === ['users', 'login'] ||
            $actionSegs === ['users', 'forgot-password'] ||
            $actionSegs === ['users', 'send-password-reset-email'] ||
            $actionSegs === ['users', 'get-remaining-session-time'] ||
            $actionSegs === ['users', 'session-info'] ||

            $actionSegs === ['users', 'logout'] ||
            $actionSegs === ['users', 'set-password'] ||
            $actionSegs === ['users', 'verify-email'] ||

            $actionSegs[0] === 'update' ||
            $actionSegs[0] === 'manualupdate' ||
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
            (
                isset($actionSegs[1]) &&
                $actionSegs[0] === 'two-factor-authentication' &&
                $actionSegs[1] === 'verify'
            ) ||
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
            $actionSegs === ['two-factor-authentication', 'settings', 'force'] ||
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

        return UrlHelper::siteUrl(TwoFactorAuth::$plugin->getSettings()->settingsPath);
    }

    /**
     * Determine valid regex.
     * @param string $string
     * @return boolean
     */
    private function isRegex(string $string)
    {
        return @preg_match($string, '') !== false;
    }
}
