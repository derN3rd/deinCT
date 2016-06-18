<?php
include_once('config.inc.php');
require_once('twitteroauth/twitteroauth.inc.php');

if(isset($_GET['oauth_token']))
{


	$connection = new TwitterOAuth($CONSUMER_KEY, $CONSUMER_SECRET, $_SESSION['request_token'], $_SESSION['request_token_secret']);
	$access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);
	if($access_token)
	{
			$connection = new TwitterOAuth($CONSUMER_KEY, $CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);
			$params =array();
			$params['include_entities']='false';
			$content = $connection->get('account/verify_credentials',$params);

			if($content && isset($content->screen_name) && isset($content->name))
			{
                unset($_SESSION['request_token']);
                unset($_SESSION['request_token_secret']);
				$_SESSION['twitter_pic']=$content->profile_image_url_https;
				$_SESSION['twitter_id']=$content->screen_name;
				$_SESSION['twitter_uid']=$content->id;
                $_SESSION['twitter_bio']=$content->description;
				@$db = new mysqli($MYSQL_HOST, $MYSQL_USER, $MYSQL_PW, $MYSQL_DB);

				    if (mysqli_connect_errno()) {
				        //printf("Datenbank Fehler\n", mysqli_connect_error());
                        mylog("dberror", "Connection error oauth.php:".mysqli_connect_error());
                        die("Datenbank-Fehler :c");
				    }else{

                        //MySQL 1
                        $stmt = $db->prepare("SELECT id,banned,admin FROM users WHERE tw_uid = ?");
                        $stmt->bind_param('s', $content->id);
                        if(false===$stmt->execute()){
                            mylog("dberror", "Check ban oauth.php: ".$stmt->error);
                            die("Datenbank-Fehler :c");
				        }
                        $stmt->bind_result($dbuserid,$dbuserbanned,$dbuseradmin);
                        $stmt->store_result();
                        $numRows = $stmt->num_rows;
                        $stmt->fetch();
                        $stmt->close();

                        //MySQL 2
                        $stmt = $db->prepare("INSERT INTO twtokens (user, token, secret) VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE token=?, secret=?");
                        $stmt->bind_param('sssss', $content->id, $access_token['oauth_token'], $access_token['oauth_token_secret'], $access_token['oauth_token'], $access_token['oauth_token_secret']);
                        if(false===$stmt->execute()){
                            mylog("dberror", "Insert/Update twtokens oauth.php: ".$stmt->error);
                            die("Datenbank-Fehler :c");
                        }
                        $stmt->close();


                        (string)$twname=$content->screen_name;
                        (string)$twid=$content->id;
                        (string)$twbild=$content->profile_image_url_https;
                        (string)$twbio=$content->description;
                        $date=new DateTime('NOW');
                        (string)$twdate=$date->format('c');
				    	if ($numRows > 0){
                            if($dbuserbanned==1){
                                mylog("user", "Tried to login while being banned");
                                session_destroy();
                                header("Location: /banned");
                                exit();
                            }
                            if($dbuseradmin==1){
                                $_SESSION['isadmin']=true;
                            }
                            //MySQL 3
				    		$stmt2 = $db->prepare("UPDATE users SET tw_name = ?, last_login = ?, tw_pic = ?, tw_bio = ? WHERE tw_uid = ?");
							$stmt2->bind_param('sssss', $twname, $twdate, $twbild, $twbio, $twid);
							if(false===$stmt2->execute()){
                                mylog("dberror", "Login Update user oauth.php: ".$stmt2->error);
                                die("Datenbank-Fehler :c");
                            }
                            mylog("user", "Login");
							$stmt2->close();
				    	}else{
                            //MySQL 3
				    		$stmt2 = $db->prepare("INSERT INTO users (tw_uid, tw_name, registered, last_login, tw_pic, tw_bio) VALUES (?, ?, ?, ?, ?, ?)");
				        	$stmt2->bind_param('ssssss', $twid, $twname, $twdate, $twdate, $twbild, $twbio);
				        	if(false===$stmt2->execute()){
                                mylog("dberror", "Login register user oauth.php: ".$stmt2->error);
                                die("Datenbank-Fehler :c");
					        }
                            mylog("user", "First Login");
					        $stmt2->close();
						}
    				}



                $connectionFol = new TwitterOAuth($CONSUMER_KEY, $CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);
                $paramsFol =array();
                $paramsFol['include_entities']='false';
                $paramsFol['screen_name']='CTDeutschland';
                $paramsFol['follow']='false';
                $contentFol = $connectionFol->post('friendships/create',$paramsFol);
                if($contentFol && isset($content->screen_name) && isset($content->name)){
                    //doit
                }
				//redirect to main page.
                if (isset($_SESSION['ref'])){
                	$path = preg_replace('#/+#', '/', urldecode($_SESSION['ref']));
                	$path = ltrim($path ,'/');
                    header("Location: /".$path);
                    unset($_SESSION['ref']);
                }else{
				    header('Location: /index');
                }

			}
			else
			{
				echo "<h4> Login Error </h4>";
                mylog("error", "Could not verify account data from twitter request oauth.php");
                session_destroy();
                header("Location: /login");
			}
	}else{
        echo "<h4> Login Error </h4>";
        mylog("error", "Got no access token from twitter oauth.php");
        session_destroy();
        header("Location: /login");
    }
}else{ //Error. redirect to Login Page.
	header('Location: /login');
}
$db->close();
?>