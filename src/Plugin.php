<?php
namespace born05\twofactorauthentication;

use born05\twofactorauthentication\widgets\Notify as NotifyWidget;

use Craft;
use born05\twofactorauthentication\services\Response as ResponseService;
use born05\twofactorauthentication\services\Verify as VerifyService;
use born05\twofactorauthentication\models\Settings;
use craft\base\Plugin as CraftPlugin;
use craft\base\Element;
use craft\elements\User;
use craft\services\Dashboard;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterElementTableAttributesEvent;
use craft\events\SetElementTableAttributeHtmlEvent;
use craft\helpers\UrlHelper;
use craft\web\UrlManager;
use yii\base\Event;
use yii\web\UserEvent;

class Plugin extends CraftPlugin
{
    /**
     * @var string
     */
    public $schemaVersion = '2.0.0';

    /**
     * @var bool
     */
    public $hasCpSection = true;

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * Plugin::$plugin
     *
     * @var Plugin
     */
    public static $plugin;

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $request = Craft::$app->getRequest();
        $response = Craft::$app->getResponse();

        if (!$this->isInstalled || $request->getIsConsoleRequest()) return;

        // Register Components (Services)
        $this->setComponents([
            'response' => ResponseService::class,
            'verify' => VerifyService::class,
        ]);

        // Only allow users who are verified or don't use two-factor.
        if ($this->shouldRedirectCp() || $this->shouldRedirectFrontEnd()) {
            // Get the current user
            $user = Craft::$app->getUser()->getIdentity();

            // Only redirect two-factor enabled users who aren't verified yet.
            if (isset($user) &&
                $this->verify->isEnabled($user) &&
                !$this->verify->isVerified($user)
            ) {
                Craft::$app->getUser()->logout(false);

                if ($request->getIsCpRequest()) {
                    $response->redirect(UrlHelper::cpUrl());
                } else {
                    $response->redirect(UrlHelper::siteUrl());
                }
            }
        }

        // Verify after login.
        Event::on(\craft\web\User::class, \craft\web\User::EVENT_AFTER_LOGIN, function(UserEvent $event) {
            // Don't redirect cookieBased events.
            if ($event->cookieBased) {
                return;
            }

            $user = Craft::$app->getUser()->getIdentity();
            $request = Craft::$app->getRequest();
            $response = Craft::$app->getResponse();

            if (isset($user) &&
                $this->verify->isEnabled($user) &&
                !$this->verify->isVerified($user)
            ) {
                if ($request->getIsCpRequest()) {
                    $url = UrlHelper::actionUrl('two-factor-authentication/verify/login');
                } else {
                    $url = UrlHelper::siteUrl($this->getSettings()->getVerifyPath());
                }

                // Redirect to verification page.
                if ($request->getAcceptsJson()) {
                    return $this->response->asJson([
                        'success' => true,
                        'returnUrl' => $url
                    ]);
                } else {
                    $response->redirect($url);
                }
            }
        });

        $this->initCp();
    }

    protected function createSettingsModel()
    {
        return new Settings();
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
        $verifyFrontEnd = $this->getSettings()->verifyFrontEnd;
        $frontEndPathWhitelist = $this->getSettings()->getFrontEndPathWhitelist();
        $frontEndPathBlacklist = $this->getSettings()->getFrontEndPathBlacklist();
        $request = Craft::$app->getRequest();
        $pathInfo = $request->getPathInfo();

        $isLoginPath = (
            $pathInfo === trim(Craft::$app->getConfig()->getGeneral()->getLoginPath(), '/') ||
            $pathInfo === trim(Craft::$app->getConfig()->getGeneral()->getLogoutPath(), '/') ||
            $pathInfo === trim($this->getSettings()->getVerifyPath(), '/')
        );

        $isWhitelisted = (
            empty($frontEndPathWhitelist) ||
            in_array($pathInfo, $frontEndPathWhitelist)
        );

        $isBlacklisted = (
            empty($frontEndPathBlacklist) ||
            in_array($pathInfo, $frontEndPathBlacklist)
        );

        return $verifyFrontEnd &&
            !$request->getIsCpRequest() &&
            !$this->isCraftSpecialRequests() &&
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
    private function is2FASpecialRequests()
    {
        $request = Craft::$app->getRequest();
        $actionSegs = $request->getActionSegments();

        return (
            $actionSegs[0] === 'two-factor-authentication' &&
            $actionSegs[1] === 'verify'
        );
    }

    private function initCp()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['two-factor-authentication'] = 'two-factor-authentication/settings/index';
        });

        // Register our widgets
        Event::on(Dashboard::class, Dashboard::EVENT_REGISTER_WIDGET_TYPES, function (RegisterComponentTypesEvent $event) {
            $event->types[] = NotifyWidget::class;
        });

        /**
         * Adds the following attributes to the User table in the CMS
         * NOTE: You still need to select them with the 'gear'
         */
        Event::on(User::class, Element::EVENT_REGISTER_TABLE_ATTRIBUTES, function(RegisterElementTableAttributesEvent $event) {
            $event->tableAttributes['hasTwoFactorAuthentication'] = ['label' => Craft::t('two-factor-authentication', '2-Factor Auth')];
        });

        /**
         * Returns the content for the additional attributes field
         */
        Event::on(User::class, Element::EVENT_SET_TABLE_ATTRIBUTE_HTML, function(SetElementTableAttributeHtmlEvent $event) {
            $currentUser = Craft::$app->getUser()->getIdentity();
            if ($event->attribute == 'hasTwoFactorAuthentication' && $currentUser->admin) {
                /** @var UserModel $user */
                $user = $event->sender;

                if (Plugin::$plugin->verify->isEnabled($user)) {
                    $event->html = '<div class="status enabled" title="' . Craft::t('two-factor-authentication', 'Enabled') . '"></div>';
                } else {
                    $event->html = '<div class="status" title="' . Craft::t('two-factor-authentication', 'Not enabled') . '"></div>';
                }

                // Prevent other event listeners from getting invoked
                $event->handled = true;
            }
        });
    }
}
