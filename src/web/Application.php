<?php 

namespace born05\twofactorauthentication\web;

use Craft;
class Application extends \craft\web\Application {
    /**
     * COPIED FROM craft\web\Application::_isSpecialCaseActionRequest
     * Returns whether this is a special case request (something dealing with user sessions or updating)
     * where system status / CP permissions shouldn't be taken into effect.
     *
     * @param Request $request
     * @return bool
     */
    private function _isSpecialCaseActionRequest(Request $request): bool
    {
        $actionSegs = $request->getActionSegments();

        if (empty($actionSegs)) {
            return false;
        }

        return (
            $actionSegs === ['app', 'migrate'] ||
            $actionSegs === ['users', 'login'] ||
            $actionSegs === ['users', 'forgot-password'] ||
            $actionSegs === ['users', 'send-password-reset-email'] ||
            $actionSegs === ['users', 'get-remaining-session-time'] ||
            (
                $request->getIsSingleActionRequest() &&
                (
                    $actionSegs === ['users', 'logout'] ||
                    $actionSegs === ['users', 'set-password'] ||
                    $actionSegs === ['users', 'verify-email']
                )
            ) ||
            (
                $request->getIsCpRequest() &&
                (
                    $actionSegs[0] === 'update' ||
                    $actionSegs[0] === 'manualupdate'
                )
            ) ||
            // Added
            (
                $actionSegs[0] === 'two-factor-authentication' &&
                $actionSegs[1] === 'verify'
            )
        );
    }
}