# WHMCS-Hook-Client-Login-Notifications
Client area login notifications for WHMCS
Notifications are only sent when the IP is different from their last login.

# Localizations / Translations
To use localizations in the email notification, follow these steps:
1. Follow the WHMCS guide to <a href="https://docs.whmcs.com/Email_Templates#Creating_Custom_Templates">creating a custom email template</a>.
2. Set the Email Type to 'General' and the Unique name must be: User Login From Different IP
3. Example Subject: "User Login from new IP {$user_ip} ({$user_hostname})"
4. Example Message: 
```
A user just accessed your account from a different IP than usual:

• Name: {$user_fullname}
• Email: {$user_email}
• IP Address: {$user_ip}
• City: {$user_city}
• Hostname: {$user_hostname}

You can manage the users allowed to access your account here: {$whmcs_url}/account/users
```
You can then set your translations in the email templates translations area.

# Original Source Code & Credits
- Found in the <a href="https://whmcs.community/topic/258817-client-account-login-notification-hook/page/4/?tab=comments#comment-1347010">WHMCS forums here</a>.
- Created by whmcsguru
- Contributions by brian!
- Rewritten by websavers

# Requirements
- WHMCS: Backwards compatibility was removed for code readability and so this only works with WHMCS 8.
- PHP: Tested with PHP 8.1. Probably works with PHP 7.4 and 8.0

# IPLocate
This code uses <a href="https://www.iplocate.io/pricing">IPLocate</a> which provides 1000 daily requests free. Anything more and you will need to pay them. Given that we only utilize the IPLocate API when an IP has changed since the last user login, the free 1000 daily lookups are probably sufficient for most WHMCS users.