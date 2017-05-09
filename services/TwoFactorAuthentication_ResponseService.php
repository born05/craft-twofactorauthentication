<?php
namespace Craft;

class TwoFactorAuthentication_ResponseService extends BaseApplicationComponent
{
    /**
     * Return the request with JSON.
     *
     * @param array $data
     */
    public function returnJson($data = array())
    {
        JsonHelper::setJsonContentTypeHeader();
        HeaderHelper::setNoCache();
        ob_start();
        echo JsonHelper::encode($data);
        craft()->end();
    }

    /**
     * Determine if the url points to the verification part of this plugin.
     *
     * @param  strin $url
     * @return boolean
     */
    public function isTwoFactorAuthenticationUrl($url)
    {
        $verifyUrl = UrlHelper::getActionUrl('twoFactorAuthentication/verify');

        return strpos($url, $verifyUrl) === 0;
    }
}
