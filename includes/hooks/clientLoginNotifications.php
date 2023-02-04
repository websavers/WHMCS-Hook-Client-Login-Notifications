<?php

use WHMCS\Database\Capsule;

add_hook('UserLogin', 1, function($vars)
{
    /*
    * If you want to send notifications when admin logs in as client, 
    * Comment the following two lines out. It's best to leave it as is.
    * https://developers.whmcs.com/advanced/authentication/
    */
    $currentUser = new \WHMCS\Authentication\CurrentUser;
    if ($currentUser->isAuthenticatedAdmin()) return;

    $user = $vars['user'];
    $userid = $user->id;

    // auth_user_id is unique
    $userobj = Capsule::table('tblusers_clients')->select('auth_user_id', 'client_id', 'owner')->where('auth_user_id', '=', $userid)->first();
    $clientid = $userobj->client_id;

    // userid is unique
    $userdata = Capsule::table('tblusers')->select('first_name', 'last_name', 'email', 'last_ip')->where('id', $userid)->first();
    $firstname = $userdata->first_name;
    $lastname = $userdata->last_name;
    $email = $userdata->email;

    $ip = $_SERVER['REMOTE_ADDR'];

    if ($userdata->last_ip === $ip){ 
        return; //Don't alert if user is logging in from the usual IP
    }

    $res = json_decode(file_get_contents('https://www.iplocate.io/api/lookup/'.$ip));
    $city = $res->city;
    $hostname = gethostbyaddr($ip);
    
    $subject = "User Login from new IP $ip ($hostname)";
    
    $message = "<p>A user just logged in to your account from a different IP than usual:</p>
        <ul>
            <li>Name: $firstname $lastname</li>
            <li>Email: $email</li>
            <li>IP Address: $ip</li>
            <li>City: $city</li>
            <li>Hostname: $hostname</li>
        </ul>
        <p>You can manage the users allowed to access your account here: {$GLOBALS['CONFIG']['SystemURL']}/account/users.</p>";

    $results = localAPI('sendemail', array(
        'customtype'        => 'general',
        'customsubject'     => $subject,
        'custommessage'     => $message,
        'id'                => $clientid,
    ));
    
});