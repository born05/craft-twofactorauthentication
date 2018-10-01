![Two-Factor Authentication](https://raw.githubusercontent.com/born05/craft-twofactorauthentication/master/plugin-icon.png)

# Two-Factor Authentication

Craft 3 plugin for two-factor or two-step login using Time Based OTP (TOTP, like Google Authenticator).
Every user can setup TOTP themselves, the plugin does not force users. Admins can list usage in user tables.

## Inner working

Login works as usual for users without 2-factor auth.

When enabled, the user is redirected to the 2-factor verification page after login.
This means the user is already logged in. When the user tries to visit an other Control Panel page than the public ones before verification, the logout is triggered. This blocks the user from visiting the CP unverified.

#### Setting screen when turning 2FA on
![Setting screen when turning 2FA on](https://raw.githubusercontent.com/born05/craft-twofactorauthentication/master/settings-turn-on.png)

#### Setting screen when turning 2FA off
![Setting screen when turning 2FA off](https://raw.githubusercontent.com/born05/craft-twofactorauthentication/master/settings-turn-off.png)

#### Login verification screen
![Login verification screen](https://raw.githubusercontent.com/born05/craft-twofactorauthentication/master/login-verification.png)

## Requirements

- Craft 3.0.0
- PHP 7.x at least

## Setting up front end 2FA

When using a login for front end users, the following steps add 2FA support.

- Build a 2FA form accessible by url
- Set `allowFrontEnd` to `true` in the config file.
- Choose between using the `frontEndPathWhitelist` or `frontEndPathBlacklist`! Using both will block everything!
- Add a template for 2FA like example below:
- Set the `verifyPath`. In our `two-factor.twig` example the path would be `two-factor`.
- Optional: allow users setting up 2FA in front end by building a template similar to: `src/templates/index.twig`

Example `two-factor.twig` template
```
{% requireLogin %}

<form method="post" accept-charset="UTF-8">
    {{ csrfInput() }}
    <input type="hidden" name="action" value="two-factor-authentication/verify/login-process">

    <h3><label for="code">Enter the authentication code from your phone.</label></h3>
    <input id="authenticationCode" type="text" name="authenticationCode">

    <input type="submit" value="Verify">
    
    {% if errorMessage is defined %}
        <p>{{ errorMessage }}</p>
    {% endif %}
    
    <script type="text/javascript">
        document.getElementById("authenticationCode").focus();
    </script>
</form>
```

## Setting up config

Create a `two-factor-authentication.php` file within your `config/` folder.

```
<?php

return [
    'verifyFrontEnd' => false,
    'forceFrontEnd' => false,
    'forceBackEnd' => false,
    
    // The URI we should use for 2FA on the front-end.
    'verifyPath' => '',
    
    // The URI we should use for 2FA settings on the front-end.
    'settingsPath' => '',

    // Choose between using the whitelist or blacklist! Using both will block everything!
    'frontEndPathWhitelist' => [
        '*' => [],
    ],
    'frontEndPathBlacklist' => [
        '*' => [],
    ],
];

```

## Resetting a user's 2FA

Simply remove the user's `twofactorauthentication_user` record. This disables 2FA for that user.

## License

Copyright © 2018 [Born05](https://www.born05.com/)

See [license](https://github.com/born05/craft-twofactorauthentication/blob/master/LICENSE.md)