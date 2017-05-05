# BETA - Two-Factor Authentication

Craft 2 plugin for two-factor or two-step login using Time Based OTP (TOTP, like Google Authenticator).
Every user can setup TOTP themselves, the plugin does not force users. Admins can list usage in user tables.

## Inner working

Login works as usual for users without 2-factor auth.

When enabled, the user is redirected to the 2-factor verification page after login.
This means the user is already logged in. When the user tries to visit an other Control Panel page than the public ones before verification, the logout is triggered. This blocks the user from visiting the CP unverified.
