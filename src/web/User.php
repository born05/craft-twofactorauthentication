<?php 

namespace born05\twofactorauthentication\web;

use Craft;
use craft\web\User as UserComponent;
use yii\web\IdentityInterface;
use born05\twofactorauthentication\Plugin as TwoFactorAuth;

class User extends UserComponent
{
    private $isVerified = null;

    /**
     * Determines whether the user has 2FA enabled and is verified.
     * @return bool
     */
    private function getIsVerified()
    {
        if (is_null($this->isVerified)) {
            $user = $this->getIdentity();

            // Make sure the user is verified when 2FA is enabled.
            if (TwoFactorAuth::$plugin->verify->isEnabled($user)) {
                $this->isVerified = TwoFactorAuth::$plugin->verify->isVerified($user);
            } else {
                // Without 2FA automatically verify.
                $this->isVerified = true;
            }
        }

        return $this->isVerified;
    }

    /**
     * Returns whether the current user is a guest.
     * @return bool
     */
    public function getIsGuestWithoutVerification()
    {
        return parent::getIsGuest();
    }

    /**
     * Returns whether the current user is not logged in or not verified.
     * @return bool
     */
    public function getIsGuest()
    {
        $isGuest = parent::getIsGuest();
        return $isGuest || !$this->getIsVerified();
    }
    
    /**
     * Returns whether the current user is an admin and verified.
     * @return bool
     */
    public function getIsAdmin(): bool
    {
        $isAdmin = parent::getIsAdmin();
        return $isAdmin && $this->getIsVerified();
    }
    
    /**
     * Logs in a user.
     * @param IdentityInterface $identity
     * @param int $duration
     * @return bool whether the user is logged in
     */
    public function login(IdentityInterface $identity, $duration = 0)
    {
        // Perform the login.
        parent::login($identity, $duration);
        
        // Determine guest with unmodified logic.
        return !parent::getIsGuest();
    }

    /**
     * Returns how many seconds are left in the current user session.
     *
     * @return int The seconds left in the session, or -1 if their session will expire when their HTTP session ends.
     */
    public function getRemainingSessionTime(): int
    {
        // Are they logged in?
        // Determine guest with unmodified logic.
        if (!parent::getIsGuest()) {
            if ($this->authTimeout === null) {
                // The session duration must have been empty (expire when the HTTP session ends)
                return -1;
            }

            $expire = Craft::$app->getSession()->get($this->authTimeoutParam);
            $time = time();

            if ($expire !== null && $expire > $time) {
                return $expire - $time;
            }
        }

        return 0;
    }
}
