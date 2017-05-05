<?php
namespace Craft;

class TwoFactorAuthenticationVariable
{
    /**
     * Determine if the current user is verified.
     * @return string
     */
    public function isUserVerified()
    {
        return craft()->twoFactorAuthentication_verify->isVerified(
            craft()->userSession->getUser()
        );
    }

    /**
     * Get the current user's secret.
     * @return string
     */
    public function getCurrentUserSecret()
    {
        $rawSecret = craft()->twoFactorAuthentication_verify->getUserSecret(
            craft()->userSession->getUser()
        );

        return str_split($rawSecret, 4);
    }

    /**
     * Get the current user's secret QR code.
     * @return string
     */
    public function getCurrentUserQRCode()
    {
        return craft()->twoFactorAuthentication_verify->getUserQRCode(
            craft()->userSession->getUser()
        );
    }
}
