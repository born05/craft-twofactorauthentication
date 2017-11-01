<?php
namespace born05\twofactorauth;

use craft\base\Element;
use craft\elements\User;
use craft\events\RegisterElementTableAttributesEvent;
use craft\events\SetElementTableAttributeHtmlEvent;
use yii\base\Event;

class Plugin extends \craft\base\Plugin
{
    public function hasCpSection()
    {
        return true;
    }

    public function init()
    {
        // Only allow users in the CP who are verified or don't use two-factor.
        $actionSegs = \Craft::$app->request->getActionSegments();
        if (
            \Craft::$app->request->isCpRequest() &&
            (
                \Craft::$app->request->getPath() !== '' &&
                $actionSegs !== array('users', 'login') &&
                $actionSegs !== array('users', 'logout') &&
                $actionSegs !== array('users', 'forgotpassword') &&
                $actionSegs !== array('users', 'sendPasswordResetEmail') &&
                $actionSegs !== array('users', 'setpassword') &&
                $actionSegs !== array('users', 'verifyemail') &&
                $actionSegs !== array('users', 'getAuthTimeout') &&
                $actionSegs[0] !== 'update'
            ) &&
            !(
                $actionSegs[0] === 'twoFactorAuthentication' &&
                $actionSegs[1] === 'verify'
            )
        ) {
            $user = \Craft::$app->user->getUser();

            // Only redirect two-factor enabled users who aren't verified yet.
            if (isset($user) &&
                \Craft::$app->twoFactorAuthentication_verify->isEnabled($user) &&
                !Craft::$app->twoFactorAuthentication_verify->isVerified($user)
            ) {
                \Craft::$app->user->logout(false);
                \Craft::$app->request->redirect(UrlHelper::getCpUrl());
            }
        }

        // Verify after login.
        \Craft::$app->on('user.onLogin', function(Event $event) {
            $user = \Craft::$app->user->getUser();

            if (isset($user) &&
                \Craft::$app->twoFactorAuthentication_verify->isEnabled($user) &&
                !Craft::$app->twoFactorAuthentication_verify->isVerified($user)
            ) {
                $url = UrlHelper::getActionUrl('twoFactorAuthentication/verify/login');

                if (Craft::$app->request->isAjaxRequest()) {
                    \Craft::$app->twoFactorAuthentication_response->returnJson(array(
                        'success' => true,
                        'returnUrl' => $url
                    ));
                } else {
                    \Craft::$app->request->redirect($url);
                }
            }
        });
        
        /**
         * Adds the following attributes to the User table in the CMS
         * NOTE: You still need to select them with the 'gear'
         */
        Event::on(User::class, Element::EVENT_REGISTER_TABLE_ATTRIBUTES, function(RegisterElementTableAttributesEvent $event) {
            $event->tableAttributes['hasTwoFactorAuthentication'] = ['label' => \Craft::t('app', '2-Factor Auth')];
        });

        /**
         * Returns the content for the additional attributes field
         */
        Event::on(User::class, Element::EVENT_SET_TABLE_ATTRIBUTE_HTML, function(SetElementTableAttributeHtmlEvent $event) {
            if ($event->attribute == 'hasTwoFactorAuthentication' && \Craft::$app->user->getUser()->admin) {
                /** @var UserModel $user */
                $user = $event->sender;

                if (Craft::$app->twoFactorAuthentication_verify->isEnabled($user)) {
                    $event->html = '<div class="status enabled" title="' . \Craft::t('app', 'Enabled') . '"></div>';
                } else {
                    $event->html = '<div class="status" title="' . \Craft::t('app', 'Not enabled') . '"></div>';
                }

                // Prevent other event listeners from getting invoked
                $event->handled = true;
            }
        });

        parent::init();
    }
}
