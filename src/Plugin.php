<?php
namespace born05\twofactorauthentication;

use born05\twofactorauthentication\widgets\Notify as NotifyWidget;

use Craft;
use born05\twofactorauthentication\services\Response as ResponseService;
use born05\twofactorauthentication\services\Verify as VerifyService;
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

        if (!$this->isInstalled) return;

        // Register Components (Services)
        $this->setComponents([
            'response' => ResponseService::class,
            'verify' => VerifyService::class,
        ]);

        // Only allow users in the CP who are verified or don't use two-factor.
        $request = Craft::$app->getRequest();
        $response = Craft::$app->getResponse();
        $actionSegs = $request->getActionSegments();

        if (
            $request->getIsCpRequest() &&
            (
                // COPIED from craft\web\Application::_isSpecialCaseActionRequest
                $request->getPathInfo() !== '' &&
                $actionSegs !== ['app', 'migrate'] &&
                $actionSegs !== ['users', 'login'] &&
                $actionSegs !== ['users', 'logout'] &&
                $actionSegs !== ['users', 'set-password'] &&
                $actionSegs !== ['users', 'verify-email'] &&
                $actionSegs !== ['users', 'forgot-password'] &&
                $actionSegs !== ['users', 'send-password-reset-email'] &&
                $actionSegs !== ['users', 'save-user'] &&
                $actionSegs !== ['users', 'get-remaining-session-time'] &&
                $actionSegs[0] !== 'updater' &&
                $actionSegs[0] !== 'debug'
            ) &&
            !(
                $actionSegs[0] === 'two-factor-authentication' &&
                $actionSegs[1] === 'verify'
            )
        ) {
            // Get the current user
            $user = Craft::$app->getUser()->getIdentity();

            // Only redirect two-factor enabled users who aren't verified yet.
            if (isset($user) &&
                $this->verify->isEnabled($user) &&
                !$this->verify->isVerified($user)
            ) {
                Craft::$app->getUser()->logout(false);
                $response->redirect(UrlHelper::cpUrl());
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
                $url = UrlHelper::actionUrl('two-factor-authentication/verify/login');

                if ($request->getAcceptsJson()) {
                    return $this->response->asJson(array(
                        'success' => true,
                        'returnUrl' => $url
                    ));
                } else {
                    $response->redirect($url);
                }
            }
        });
        
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
            $event->tableAttributes['hasTwoFactorAuthentication'] = ['label' => Craft::t('app', '2-Factor Auth')];
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
                    $event->html = '<div class="status enabled" title="' . Craft::t('app', 'Enabled') . '"></div>';
                } else {
                    $event->html = '<div class="status" title="' . Craft::t('app', 'Not enabled') . '"></div>';
                }

                // Prevent other event listeners from getting invoked
                $event->handled = true;
            }
        });
    }
}
