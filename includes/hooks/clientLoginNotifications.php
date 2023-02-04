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

    $userobj = Capsule::table('tblusers_clients')
        ->join('tblusers', 'tblusers_clients.auth_user_id', '=', 'tblusers.id')
        ->select('tblusers_clients.client_id', 'tblusers_clients.owner', 'tblusers.first_name', 'tblusers.last_name', 'tblusers.last_ip')
        ->where('tblusers_clients.auth_user_id', '=', $user->id)
        ->first();

    $clientid   = $userobj->client_id;
    $firstname  = $userobj->first_name;
    $lastname   = $userobj->last_name;
    $email      = $userobj->email;
    $last_ip    = $userobj->last_ip;

    $ip = $_SERVER['REMOTE_ADDR'];

    if ($last_ip === $ip){ 
        return; //Don't alert if user is logging in from the usual IP
    }

    $res = json_decode(file_get_contents('https://www.iplocate.io/api/lookup/'.$ip));
    $city = $res->city;
    $hostname = gethostbyaddr($ip);
    
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