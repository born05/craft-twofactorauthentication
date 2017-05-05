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
}
