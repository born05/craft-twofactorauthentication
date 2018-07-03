<?php
namespace born05\twofactorauthentication;

use born05\twofactorauthentication\widgets\Notify as NotifyWidget;

use Craft;
use craft\base\Plugin as CraftPlugin;
use craft\base\Element;
// use craft\elements\User;
use craft\services\Dashboard;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterElementTableAttributesEvent;
use craft\events\SetElementTableAttributeHtmlEvent;
use craft\helpers\UrlHelper;
use craft\web\UrlManager;
use yii\base\Event;
use yii\web\UserEvent;
use born05\twofactorauthentication\web\User;

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

        // Verify after login.
        Event::on(\craft\web\User::class, \craft\web\User::EVENT_AFTER_LOGIN, function(UserEvent $event) {
            $user = Craft::$app->getUser()->getIdentity();
            $request = Craft::$app->getRequest();
            $response = Craft::$app->getResponse();

            if (isset($user) &&
                Plugin::$plugin->verify->isEnabled($user) &&
                !Plugin::$plugin->verify->isVerified($user)
            ) {
                $url = UrlHelper::actionUrl('two-factor-authentication/verify/login');

                if ($request->getAcceptsJson()) {
                    return Plugin::$plugin->response->asJson(array(
                        'success' => true,
                        'returnUrl' => $url
                    ));
                } else {
                    $response->redirect($url);
                }
            }
        });
        
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['two-factor-authentication'] = 'two-factor-authentication/default/index';
        });
        
        // Register our widgets
        Event::on(Dashboard::class, Dashboard::EVENT_REGISTER_WIDGET_TYPES, function (RegisterComponentTypesEvent $event) {
            $event->types[] = NotifyWidget::class;
        });
        
        /**
         * Adds the following attributes to the User table in the CMS
         * NOTE: You still need to select them with the 'gear'
         */
        Event::on(\craft\elements\User::class, Element::EVENT_REGISTER_TABLE_ATTRIBUTES, function(RegisterElementTableAttributesEvent $event) {
            $event->tableAttributes['hasTwoFactorAuthentication'] = ['label' => Craft::t('two-factor-authentication', '2-Factor Auth')];
        });

        /**
         * Returns the content for the additional attributes field
         */
        Event::on(\craft\elements\User::class, Element::EVENT_SET_TABLE_ATTRIBUTE_HTML, function(SetElementTableAttributeHtmlEvent $event) {
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
    
    /**
     * Replacement for the default app.web.php user.
     * COPIED from vendor/craftcms/cms/src/config/app.web.php 'user'
     */
    public static function userConfig()
    {
        $configService = Craft::$app->getConfig();
        $generalConfig = $configService->getGeneral();
        $request = Craft::$app->getRequest();

        if ($request->getIsConsoleRequest() || $request->getIsSiteRequest()) {
            $loginUrl = \craft\helpers\UrlHelper::siteUrl($generalConfig->getLoginPath());
        } else {
            $loginUrl = \craft\helpers\UrlHelper::cpUrl('login');
        }

        $stateKeyPrefix = md5('Craft.' . \craft\web\User::class . '.' . Craft::$app->id);

        return Craft::createObject([
            'class' => User::class,
            'identityClass' => \craft\elements\User::class,
            'enableAutoLogin' => true,
            'autoRenewCookie' => true,
            'loginUrl' => $loginUrl,
            'authTimeout' => $generalConfig->userSessionDuration ?: null,
            'identityCookie' => Craft::cookieConfig(['name' => $stateKeyPrefix . '_identity']),
            'usernameCookie' => Craft::cookieConfig(['name' => $stateKeyPrefix . '_username']),
            'idParam' => $stateKeyPrefix . '__id',
            'authTimeoutParam' => $stateKeyPrefix . '__expire',
            'absoluteAuthTimeoutParam' => $stateKeyPrefix . '__absoluteExpire',
            'returnUrlParam' => $stateKeyPrefix . '__returnUrl',
        ]);
    }
}
