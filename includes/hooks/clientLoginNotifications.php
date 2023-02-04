<?php

use WHMCS\Database\Capsule;
$whmcsver = get_whmcs_version();

/*
 * If you want to send notifications when admin logged in as client, change this to true.
 * It's best not to do this as it will let your clients know when you're logging into their account
 */
$notify_when_admin = FALSE;

function hook_client_login_notify($vars)
{
    if (is_admin() && !$notify_when_admin)
    { 
        return;
    }

	$mailsent=FALSE;

	global $whmcsver;
	$whmcsver = get_whmcs_version();
	if ($whmcsver < 8)
	{
		$userid = $vars['userid'];

		send_login_notify($userid);
		return;
	}
	if ($whmcsver >= 8)
	{
		$user = $vars['user'];
		$userid = $user->id;
		//a dirty hack to try to work around a couple of things, maybe

		$acctowner = Capsule::table('tblusers_clients')
		->where('auth_user_id', '=', $userid)
		->where('owner', '=', 1)
		->count();

		$numrows = Capsule::table('tblusers_clients')
		->where('auth_user_id', '=', $userid)
		->count();

		//we own our account. We must always notify us directly
		if ($acctowner > 0)
		{
			send_login_notify($userid);
			return;
		}

		//we don't own our account, so, notify the owner, if we only exist once.
		if ($numrows < 2)
		{
			foreach (Capsule::table('tblusers_clients')->WHERE('auth_user_id', '=', $userid)->get() as $userstuff){
				$userid = $userstuff->auth_user_id;
				$clientid = $userstuff->client_id;
				$owner = $owner;
				if ($acctowner < 1)
				{
					send_login_notify($clientid, $userid);
					return;
				}

			}
		}

		return;
	}



}


function send_login_notify($myclient, $theuserid="")
{
	global $whmcsver;

	$ip = $_SERVER['REMOTE_ADDR'];

	$res = json_decode(file_get_contents('https://www.iplocate.io/api/lookup/'.$ip));
	$city = $res->city;
	$hostname = gethostbyaddr($ip);

	if ($whmcsver < 8)
	{

		$clientinfo = Capsule::table('tblclients')->select('firstname', 'lastname')->WHERE('id', $myclient)->get();
		foreach ($clientinfo as $clrow)
		{
			$firstname = $clrow->firstname;
			$lastname = $clrow->lastname;
		}
	}
	if ($whmcsver >= 8)
	{

		$clientinfo = Capsule::table('tblusers')->select('first_name', 'last_name')->WHERE('id', $myclient)->get();
		foreach ($clientinfo as $clrow)
		{
			$firstname = $clrow->first_name;
			$lastname = $clrow->last_name;
		}
	}


	$values["customtype"] = "general";
	if (empty($theuserid))
	{
		$values["customsubject"] = "Account Login from $hostname";
		$values["custommessage"] = "<p>Hello $firstname $lastname,<p>Your account was recently successfully accessed by a remote user. If this was not you, please contact us immediately<p>IP Address: $ip<br/>City: $city<br/>Hostname: $hostname<br />";
	}

	elseif ($theuserid > 0)
	{
		$moreinfo = Capsule::table('tblusers')->select('first_name', 'last_name', 'email')->WHERE('id', $theuserid)->get();
		//greet them
		foreach ($moreinfo as $userrow)
		{
			$ufirst = $userrow->first_name;
			$ulast = $userrow->last_name;
			$uemail = $userrow->email;
		}

		$values["customsubject"] = "Subaccount Login from $hostname";
		$values["custommessage"] = "<p>Hello
		$firstname $lastname,<p>
		A subaccount of yours just logged in. Please see the details of the login below
		<p>
		Name: $ufirst $ulast
		Email: $uemail
		IP Address: $ip
		City: $city
		Hostname: $hostname<br />";
	}
	$values["id"] = $myclient;

	$results = localAPI('sendemail', $values);
	
}

$hookname = ($whmcsver >= 8)? 'UserLogin':'ClientLogin';
add_hook($hookname, 1, 'hook_client_login_notify');

function get_whmcs_version()
{
        $theversion = Capsule::table('tblconfiguration')->where('setting', '=', 'Version')->value('value');
        $majorver = substr($theversion, 0,1);

        return ($majorver);
}

function is_admin()
{
    $adminid = $_SESSION['adminid'];
    return !empty($adminid);
}