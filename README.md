# WHMCS-Hook-Client-Login-Notifications
Client area login notifications for WHMCS
Notifications are only sent when the IP is different from their last login.

# Localizations / Translations
To use localizations in the email notification, follow these steps:
1. Follow the WHMCS guide to <a href="https://docs.whmcs.com/Email_Templates#Creating_Custom_Templates">creating a custom email template</a>.
2. Set the Email Type to 'General' and the Unique name must be: User Login From Different IP
3. Example Subject: "User Login from new IP {$user_ip} ({$user_hostname})"
4. Click the 'source code' button and paste in this example message: 
```
<p>A user just accessed your account from a different IP than usual:</p>
<ul>
<li>Name: {$user_fullname}</li>
<li>Email: {$user_email}</li>
<li>IP Address: {$user_ip}</li>
<li>City: {$user_city}</li>
<li>Hostname: {$user_hostname}</li>
</ul>
<p>You can manage the users allowed to access your account here: {$whmcs_url}/account/users.</p>
```
You can then set your translations in the email templates translations area.

# Original Source
https://whmcs.community/topic/258817-client-account-login-notification-hook/page/4/?tab=comments#comment-1347010

# Credits
Created by whmcsguru
Contributions by brian!
Rewritten by websavers

# Requirements
Backwards compatibility was removed for code readability and so this only works with WHMCS 8.
Tested with PHP 8.1. Probably works with PHP 7.4 and 8.0