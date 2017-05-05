<?php
namespace Craft;

class TwoFactorAuthentication_NotifyWidget extends BaseWidget
{
    public function getName()
    {
        return Craft::t('Two-factor authentication status');
    }

    public function getBodyHtml()
    {
        $user = craft()->userSession->getUser();

        return craft()->templates->render('twofactorauthentication/_widgets/status/body', array(
            'isEnabled' => craft()->twoFactorAuthentication_verify->isEnabled($user),
        ));
    }
}
