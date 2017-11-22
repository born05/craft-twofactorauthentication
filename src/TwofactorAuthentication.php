<?php
namespace born05\twofactorauthentication;

use born05\twofactorauthentication\widgets\Notify as NotifyWidget;

use Craft;
use craft\base\Plugin;
use craft\base\Element;
use craft\elements\User;
use craft\services\Dashboard;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterElementTableAttributesEvent;
use craft\events\SetElementTableAttributeHtmlEvent;
use yii\base\Event;

class TwofactorAuthentication extends Plugin
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
     * TwofactorAuthentication::$plugin
     *
     * @var TwofactorAuthentication
     */
    public static $plugin;

    public function init()
    {
        parent::init();
        self::$plugin = $this;
        
        if (!$this->isInstalled) return;

        // Only allow users in the CP who are verified or don't use two-factor.
        $request = Craft::$app->getRequest();
        $actionSegs = $request->getActionSegments();

        if (
            $request->getIsCpRequest() &&
            (
                $request->getPathInfo() !== '' &&
                $actionSegs !== array('users', 'login') &&
                $actionSegs !== array('users', 'logout') &&
                $actionSegs !== array('users', 'get-remaining-session-time') &&
                $actionSegs !== array('users', 'send-password-reset-email') &&
                $actionSegs !== array('users', 'send-activation-email') &&
                $actionSegs !== array('users', 'save-user') &&
                $actionSegs !== array('users', 'set-password') &&
                $actionSegs !== array('users', 'verify-email') &&
                $actionSegs[0] !== 'update'
            ) &&
            !(
                $actionSegs[0] === 'twoFactorAuthentication' &&
                $actionSegs[1] === 'verify'
            )
        ) {
            // Get the current user
            $user = Craft::$app->getUser()->getIdentity();

            // Only redirect two-factor enabled users who aren't verified yet.
            if (isset($user) &&
                TwofactorAuthentication::$plugin->verify->isEnabled($user) &&
                !TwofactorAuthentication::$plugin->verify->isVerified($user)
            ) {
                Craft::$app->getUser()->logout(false);
                $request->redirect(UrlHelper::getCpUrl());
            }
        }

        // Verify after login.
        Craft::$app->on('user.onLogin', function(Event $event) {
            $user = Craft::$app->getUser()->getIdentity();

            if (isset($user) &&
                TwofactorAuthentication::$plugin->verify->isEnabled($user) &&
                !TwofactorAuthentication::$plugin->verify->isVerified($user)
            ) {
                $url = UrlHelper::getActionUrl('twoFactorAuthentication/verify/login');

                if ($request->isAjaxRequest()) {
                    TwofactorAuthentication::$plugin->response->returnJson(array(
                        'success' => true,
                        'returnUrl' => $url
                    ));
                } else {
                    $request->redirect($url);
                }
            }
        });
        
        // Register our widgets
        Event::on(
            Dashboard::class,
            Dashboard::EVENT_REGISTER_WIDGET_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = NotifyWidget::class;
            }
        );
        
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
            if ($event->attribute == 'hasTwoFactorAuthentication' && Craft::$app->user->getUser()->admin) {
                /** @var UserModel $user */
                $user = $event->sender;

                if (TwofactorAuthentication::$plugin->verify->isEnabled($user)) {
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
