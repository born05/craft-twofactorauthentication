# BETA - Two-Factor Authentication

Craft 2 plugin for two-factor or two-step login using Time Based OTP (TOTP, like Google Authenticator).
Every user can setup TOTP themselves, the plugin does not force users. Admins can list usage in user tables.

## Inner working

Login works as usual for users without 2-factor auth.

When enabled, the user is redirected to the 2-factor verification page after login.
This means the user is already logged in. When the user tries to visit an other Control Panel page than the public ones before verification, the logout is triggered. This blocks the user from visiting the CP unverified.

## Requirements

- Craft 2.6.x (we test on the latest release of Craft 2)
- PHP 7.1 at least (leans on [otphp](https://github.com/Spomky-Labs/otphp))

## Resetting a user's 2FA

Simply remove the user's `TwoFactorAuthentication_UserRecord`. This disables 2FA for that user.

## License

See [license](https://github.com/born05/craft-twofactorauthentication/blob/master/LICENSE)