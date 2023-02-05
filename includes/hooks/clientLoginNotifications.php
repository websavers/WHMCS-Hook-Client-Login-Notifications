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

    if ($DEBUG_USER_ID != $user->id) return;

    $userobj = Capsule::table('tblusers_clients')
        ->join('tblusers', 'tblusers_clients.auth_user_id', '=', 'tblusers.id')
        ->select('tblusers_clients.client_id', 'tblusers_clients.owner', 'tblusers.first_name', 'tblusers.last_name', 'tblusers.email', 'tblusers.last_ip')
        ->where('tblusers_clients.auth_user_id', '=', $user->id)
        ->first();

    $clientid   = $userobj->client_id;
    $firstname  = $userobj->first_name;
    $lastname   = $userobj->last_name;
    $email      = $userobj->email;
    $last_ip    = $userobj->last_ip;

    $ip = $_SERVER['REMOTE_ADDR'];

    //logActivity(print_r($user, 1));
    logActivity("Cur IP: $ip | Last IP: {$user->lastIp}"); ///DEBUG

    if ($last_ip === $ip){ 
        return; // Don't alert if user is logging in from the usual IP
    }

    $res = json_decode(file_get_contents('https://www.iplocate.io/api/lookup/'.$ip));
    $city = $res->city;
    $hostname = gethostbyaddr($ip);

    // If email template exists, send using that
    $templates = localAPI('GetEmailTemplates', array('type' => 'general'));
    foreach($tempaltes['emailtemplates']['emailtemplate'] as $template ){
        if ($template['name'] === $EMAIL_TEMPLATE){
            $results = localAPI('SendEmail', array(
                'messagename'   => $EMAIL_TEMPLATE,
                'id'            => $clientid,
                'customvars'    => base64_encode(serialize(array(
                    'user_fullname'      => "$firstname $lastname",
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
            <li>Name: $firstname $lastname</li>
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