<?php
include_once('config.inc.php');
require_once('twitteroauth/twitteroauth.inc.php');


if(isloggedin()) //check whether user already logged in with twitter
{
	header('Location: /index');
}
else // Not logged in
{
	$connection = new TwitterOAuth($CONSUMER_KEY, $CONSUMER_SECRET);
	$request_token = $connection->getRequestToken($OAUTH_CALLBACK); //get Request Token

	if(	$request_token)
	{
		$token = $request_token['oauth_token'];
		$_SESSION['request_token'] = $token ;
		$_SESSION['request_token_secret'] = $request_token['oauth_token_secret'];

		switch ($connection->http_code)
		{
			case 200:
				$url = $connection->getAuthorizeURL($token);
				//redirect to Twitter .
		    	header('Location: ' . $url);
			    break;
			default:
			    echo "Connection with twitter Failed";
                mylog("error", "Connection with twitter failed login.php");
		    	break;
		}

	}
	else //error receiving request token
	{
		die("Twitter-Fehler");
        mylog("error", "Error Receiving Request Token login.php");
	}


}



?>