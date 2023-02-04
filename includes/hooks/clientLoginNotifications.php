<?php

use WHMCS\Database\Capsule;

/*
 * If you want to send notifications when admin logged in as client, change this to true.
 * It's best not to do this as it will let your clients know when you're logging into their account
 */
$notify_when_admin = FALSE;

add_hook('UserLogin', 1, function($vars)
{
    if (is_admin() && !$notify_when_admin)
    { 
        return;
    }

    $user = $vars['user'];
    $userid = $user->id;

    // auth_user_id is unique
    $userobj = Capsule::table('tblusers_clients')->select('auth_user_id', 'client_id', 'owner')->where('auth_user_id', '=', $userid)->get();

    //User is Owner of the account
    if ($userobj->owner == 1)
    {
        send_login_notify($userobj->client_id);
    }

    else //User is not owner of the account: notify the owner
    {
        send_login_notify($userobj->client_id, $userid);
    }

});


function send_login_notify($clientid, $theuserid="")
{

	$ip = $_SERVER['REMOTE_ADDR'];

	$res = json_decode(file_get_contents('https://www.iplocate.io/api/lookup/'.$ip));
	$city = $res->city;
	$hostname = gethostbyaddr($ip);

    $clientdata = Capsule::table('tblclients')->select('firstname', 'lastname', 'email')->where('id', $clientid)->first();
    $firstname = $clientdata->firstname;
    $lastname = $clientdata->lastname;
    $email = $clientdata->email;

	if (!empty($theuserid)) //replace client data with user data
	{
		$userdata = Capsule::table('tblusers')->select('first_name', 'last_name', 'email')->where('id', $theuserid)->first();
		$firstname = $userdata->first_name;
		$lastname = $userdata->last_name;
		$email = $userdata->email;
    }
    
    $subject = "User Login from $hostname";
    
    $message = "<p>Hello $firstname $lastname,</p>
        <p>A user just logged in to your account:</p>
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
	
}

function is_admin()
{
    $adminid = $_SESSION['adminid'];
    return !empty($adminid);
}