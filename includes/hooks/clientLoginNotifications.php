<?php

use WHMCS\Database\Capsule;

add_hook('UserLogin', 1, function($vars)
{
    /* 
     * Set this to a specific user ID (NOTE: not client ID) to enable debug mode
     * When debug mode is enabled, this hook will only execute fully upon 
     * login by the user designated here.
     */
    $DEBUG_USER_ID = 149;

    /* 
     * If you wish to use localizations for the email notification, 
     * create an email template with this name in the WHMCS admin.
     * Details in README.md
     */
    $EMAIL_TEMPLATE = 'User Login From Different IP';

    /*
     * If you want to send notifications when admin logs in as client, 
     * Comment the following two lines out. It's best to leave it as is.
     * https://developers.whmcs.com/advanced/authentication/
     */
    $currentUser = new \WHMCS\Authentication\CurrentUser;
    if ($currentUser->isAuthenticatedAdmin()) return;

    $user = $vars['user'];

    /* 
     * This is necessary to create historical records of logins
     * Because WHMCS does not save past login IP/Hostname values: only current.
     */
    $log_description = "User {$user->id} logged in";
    logActivity($log_description);

    if ($DEBUG_USER_ID != $user->id) return;

    $fullname   = "{$user->firstName} {$user->lastName}";
    $email      = $user->email;
    $ip         = $user->lastIp;
    $hostname   = $user->lastHostname;

    /* WHMCS updates this data in the DB *before* this hook runs */
    $clientid = Capsule::table('tblusers_clients')
        ->where('auth_user_id', '=', $user->id)
        ->where('owner', '=', 1)
        ->value('client_id');

    /*
     * Obtain the last IP address used to login
     * https://developers.whmcs.com/api-reference/getactivitylog/
     */
    $logentries = localAPI('GetActivityLog', array(
        'user'          => $email, //docs are wrong about it being name, it should be email.
        'description'   => $log_description,
        'limitnum'      => 2 // for performance, when log entries increase in number
    ));
    //logActivity(print_r($logentries, 1)); ///DEBUG
    if ($logentries['totalresults'] < 2){
        // No history of logins (other than this one) since this hook was added, so don't proceed
        return;
    }
    // The most recent log entry (index 0) should be the one we just logged,
    // so get the one before that (index 1)
    $last_ip = $logentries['activity']['entry'][1]['ipaddress'];
    //logActivity("Cur IP: $ip | Last IP: $last_ip"); ///DEBUG

    // Don't alert if user is logging in from the same IP as last time
    if ($last_ip === $ip) return;

    //$ip = $_SERVER['REMOTE_ADDR'];
    //$hostname = gethostbyaddr($ip);
    $res = json_decode(file_get_contents('https://www.iplocate.io/api/lookup/'.$ip));
    $city = $res->city;

    // If email template exists, send using that
    $templates = localAPI('GetEmailTemplates', array('type' => 'general'));
    foreach($tempaltes['emailtemplates']['emailtemplate'] as $template ){
        if ($template['name'] === $EMAIL_TEMPLATE){
            $results = localAPI('SendEmail', array(
                'messagename'   => $EMAIL_TEMPLATE,
                'id'            => $clientid,
                'customvars'    => base64_encode(serialize(array(
                    'user_fullname'      => $fullname,
                    'user_city'          => $city,
                    'user_ip'            => $ip,
                    'user_hostname'      => $hostname,
                ))),
            ));
            return;
        }
    }

    // No email template found, so fallback to send using custom data
    $subject = "User Login from new IP $ip ($hostname)";    
    $message = "<p>A user just accessed your account from a different IP than usual:</p>
        <ul>
            <li>Name: $fullname</li>
            <li>Email: $email</li>
            <li>IP Address: $ip</li>
            <li>City: $city</li>
            <li>Hostname: $hostname</li>
        </ul>
        <p>You can manage the users allowed to access your account here: {$GLOBALS['CONFIG']['SystemURL']}/account/users.</p>";

    $results = localAPI('SendEmail', array(
        'customtype'        => 'general',
        'customsubject'     => $subject,
        'custommessage'     => $message,
        'id'                => $clientid,
    ));
    
});