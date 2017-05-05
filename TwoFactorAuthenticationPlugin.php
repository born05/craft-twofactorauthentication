<?php
namespace Craft;

class TwoFactorAuthenticationPlugin extends BasePlugin
{
    public function getName()
    {
        return Craft::t('Two-Factor Authentication');
    }

    public function getVersion()
    {
        return '0.0.1';
    }

    public function getSchemaVersion()
    {
        return '0.0.1';
    }

    public function getDeveloper()
    {
        return 'Born05';
    }

    public function getDeveloperUrl()
    {
        return 'http://www.born05.com/';
    }

    public function hasCpSection()
    {
        return true;
    }

    public function init()
    {
        parent::init();

        // Only allow users in the CP who are verified or don't use two-factor.
        $actionSegs = craft()->request->getActionSegments();
        if (
            craft()->request->isCpRequest() &&
            (
                craft()->request->getPath() !== '' &&
                $actionSegs !== array('users', 'login') &&
                $actionSegs !== array('users', 'logout') &&
                $actionSegs !== array('users', 'forgotpassword') &&
                $actionSegs !== array('users', 'sendPasswordResetEmail') &&
                $actionSegs !== array('users', 'setpassword') &&
                $actionSegs !== array('users', 'verifyemail') &&
                $actionSegs[0] !== 'update'
            ) &&
            !(
                $actionSegs[0] === 'twoFactorAuthentication' &&
                $actionSegs[1] === 'verify'
            )
        ) {
            $user = craft()->userSession->getUser();

            // Only redirect two-factor enabled users who aren't verified yet.
            if (isset($user) &&
                craft()->twoFactorAuthentication_verify->isEnabled($user) &&
                !craft()->twoFactorAuthentication_verify->isVerified($user)
            ) {
                craft()->userSession->logout(false);
                craft()->request->redirect(UrlHelper::getCpUrl());
            }
        }

        // Verify after login.
        craft()->on('userSession.onLogin', function(Event $event) {
            $user = craft()->userSession->getUser();

            if (isset($user) &&
                craft()->twoFactorAuthentication_verify->isEnabled($user) &&
                !craft()->twoFactorAuthentication_verify->isVerified($user)) {
                $url = UrlHelper::getActionUrl('twoFactorAuthentication/verify/login');

                if (craft()->request->isAjaxRequest()) {
                    craft()->twoFactorAuthentication_response->returnJson(array(
                        'success' => true,
                        'returnUrl' => $url
                    ));
                } else {
                    craft()->request->redirect($url);
                }
            }
        });
    }

    /**
     * Adds the following attributes to the User table in the CMS
     * NOTE: You still need to select them with the 'gear'
     *
     * @return array
     */
    public function defineAdditionalUserTableAttributes()
    {
        if (craft()->userSession->getUser()->admin) {
            return array(
                'hasTwoFactorAuthentication' => Craft::t('2-Factor Auth'),
            );
        }
    }

    /**
     * Returns the content for the additional attributes field
     *
     * @param UserModel $user
     * @param string $attribute
     * @return string The content for the field
     */
    public function getUserTableAttributeHtml(UserModel $user, $attribute)
    {
        if ($attribute == 'hasTwoFactorAuthentication' && craft()->userSession->getUser()->admin) {
            if (craft()->twoFactorAuthentication_verify->isEnabled($user)) {
                return '<div class="status enabled" title="' . Craft::t('Enabled') . '"></div>';
            } else {
                return '<div class="status" title="' . Craft::t('Not enabled') . '"></div>';
            }
        }
    }
}
