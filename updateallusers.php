<?php
$startzeit = microtime(true);
echo "------------------[deinCT-Cron] Daily Updates ".date("d.m.Y H:i:s")." starting\n";
require_once('twitteroauth/TwitterAPIExchange.inc.php');
require_once('inc/log.php');
$MYSQL_USER='user';
$MYSQL_PW='pass';
$MYSQL_HOST='localhost';
$MYSQL_DB='deinCT';
$error="";
$userblock=0;
$successcounterall=0;

@$db = new mysqli($MYSQL_HOST, $MYSQL_USER, $MYSQL_PW, $MYSQL_DB);

if (mysqli_connect_errno()) {
    //trigger_error('MySQL-Error: Fehler beim Verbinden zum Datenbank Server: '.mysqli_connect_error(), E_USER_ERROR);
    myLog("error","Could not connect to db server: ".mysqli_connect_error());
    die("Datenbank Fehler");
}

//ANZAHL AN USERN ABFRAGEN
$stmt = $db->prepare("SELECT COUNT(*) FROM users");
if(false===$stmt->execute()){
    //trigger_error('MySQL-Error: Fehler beim holen der Useranzahl: '.$stmt->error, E_USER_ERROR);
    myLog("error","Error while fetching usercount: ".$stmt->error);
    die("Datenbank-Fehler :c");
}
$stmt->bind_result($useranzahl);
$stmt->fetch();
$stmt->close();
(int)$useranzahl=$useranzahl+1;


while($userblock < $useranzahl){
    $userblockend=$userblock+99;
    echo "[User ".$userblock." bis ".($userblockend)."] Beginne mit Block.".PHP_EOL;
    $stmt = $db->prepare("SELECT id, tw_uid, tw_name FROM users LIMIT ?, 99");
    $stmt->bind_param('i', $userblock);
    if(false===$stmt->execute()){
        //trigger_error('MySQL-Error: Fehler beim holen von Usern: '.$stmt->error, E_USER_ERROR);
        myLog("error","Error while fetching user: ".$stmt->error);
        die("stmt fehler");
    }
    $stmt->bind_result($dbid,$dbtw_uid,$dbtw_name);
    $stmt->store_result();
    $numRows = $stmt->num_rows;
    $alltwitaccs = "";
    while($stmt->fetch()){
        $alltwitaccs .= $dbtw_uid.",";
    }
    $stmt->close();
    $alltwitaccs = substr($alltwitaccs, 0, -1);


    /** Set access tokens here - see: https://dev.twitter.com/apps/ **/
    $settings = array(
        'oauth_access_token' => "",
        'oauth_access_token_secret' => "",
        'consumer_key' => "",
        'consumer_secret' => ""
    );
    $url = 'https://api.twitter.com/1.1/users/lookup.json';
    $requestMethod = 'POST';

    $postfields = array(
        'user_id' => $alltwitaccs,
        'include_entities' => 'false');
    $twitter = new TwitterAPIExchange($settings);
    $infos_raw = $twitter->buildOauth($url, $requestMethod)
             ->setPostfields($postfields)
             ->performRequest();
    echo "[User ".$userblock." bis ".($userblockend)."] Hole Twitter-Daten".PHP_EOL;
    $infos = json_decode($infos_raw, true);

    if(isset($infos["errors"])){
        $error .= $infos["errors"][0]["message"];
        //trigger_error('Twitter-Error: '.$error, E_USER_ERROR);
        myLog("error","Error while fetching twitterdata: ".$error);
        die("Twitter-ERROR: ".$error);
    }

    $successcounter=0;
    $updatedcounter=0;
    foreach ($infos as $key => $val){
        $stmt = $db->prepare("UPDATE users SET tw_name = ?, tw_pic = ?, tw_bio = ?, last_login=last_login WHERE tw_uid = ?");
        $stmt->bind_param('ssss',
        $infos[$key]["screen_name"],
        $infos[$key]["profile_image_url_https"],
        $infos[$key]["description"],
        $infos[$key]["id_str"]);
        if(false===$stmt->execute()){
            echo "Fehler beim Updaten von @".$val["screen_name"].PHP_EOL;
            myLog("error","Error while updating user @".$val["screen_name"].": ".$stmt->error);
            trigger_error('MySQL-Error: Fehler beim Updaten eines Users: '.$stmt->error, E_USER_ERROR);
        }else{
            //echo "Profil von @".$val["screen_name"]." wurde aktualisiert\n";
            $successcounter++;
        }
        $updatedcounter = $updatedcounter + ($stmt->affected_rows);
        $stmt->close();
    }
    echo "[User ".$userblock." bis ".($userblockend)."] ".$successcounter." User im Block geupdatet.".PHP_EOL;
    $successcounterall=$successcounterall+$successcounter;
    $userblock=$userblockend;
}//while end
echo "Insgesamt ".$successcounterall." von ".($useranzahl-1)." Usern geupdatet.".PHP_EOL;

//-------------------------------------------------------------------------------------------------------------------------//

//ANZAHL AN USERN ABFRAGEN
$stmt = $db->prepare("SELECT COUNT(*) FROM users");
if(false===$stmt->execute()){
    die("Datenbank-Fehler :c");
}else{
    $stmt->bind_result($useranzahl);
    $stmt->fetch();
    $stmt->close();
    //Upload
    $stmt2 = $db->prepare("UPDATE stats SET last = current, current = ? WHERE type = ?");
    $mytype="user";
    $stmt2->bind_param('is', $useranzahl, $mytype);
    if(false===$stmt2->execute()){
        trigger_error('MySQL-Error: Fehler beim Updaten der User-Stats: '.$stmt2->error, E_USER_ERROR);
        echo "Could not update user stats.".PHP_EOL;
    }
    $stmt2->close();
    echo "User Statistiken geupdatet".PHP_EOL;
}

//ANZAHL AN CTS ABFRAGEN
$stmt = $db->prepare("SELECT COUNT(*) FROM cts");
if(false===$stmt->execute()){
    die("Datenbank-Fehler :c");
}else{
    $stmt->bind_result($ctsanzahl);
    $stmt->fetch();
    $stmt->close();
    //Upload
    $stmt2 = $db->prepare("UPDATE stats SET last = current, current = ? WHERE type = ?");
    $mytype="cts";
    $stmt2->bind_param('is', $ctsanzahl, $mytype);
    if(false===$stmt2->execute()){
        trigger_error('MySQL-Error: Fehler beim Updaten der CT-Stats: '.$stmt2->error, E_USER_ERROR);
        echo "Could not update ct stats.".PHP_EOL;
    }
    $stmt2->close();
    echo "CT Statistiken geupdatet".PHP_EOL;
}

//ANZAHL AN LOGINS ABFRAGEN
/*$time30daysago=strtotime('-30 days');
$stmt = $db->prepare("SELECT COUNT(*) FROM log WHERE type='user' AND message='Login' AND time>$time30daysago");
if(false===$stmt->execute()){
    die("Datenbank-Fehler :c");
}else{
    $stmt->bind_result($loginanzahl);
    $stmt->fetch();
    $stmt->close();
    //Upload
    $stmt2 = $db->prepare("UPDATE stats SET last = current, current = ? WHERE type = ?");
    $mytype="login";
    $stmt2->bind_param('is', $loginanzahl, $mytype);
    if(false===$stmt2->execute()){
        echo "Could not update login stats.".PHP_EOL;
    }
    $stmt2->close();
    echo "Login Statistiken geupdatet".PHP_EOL;
}*/

//ANZAHL EINZELNER USER PRO CT ABFRAGEN
$stmt = $db->prepare("SELECT ctguests.ctid,COUNT(*) FROM ctguests GROUP BY ctguests.ctid");
if(false===$stmt->execute()){
    die("Datenbank-Fehler :c");
}
$stmt->bind_result($ctid, $usercount);
$ctstats=array();
while ($stmt->fetch()) {
    $ctstats[]=array("ctid"=>$ctid,"usercount"=>$usercount);
}
$stmt->close();

foreach ($ctstats as $ctstat){
    $stmt2 = $db->prepare("UPDATE ctstats SET last = current, current = ? WHERE ctid = ?");
    $stmt2->bind_param('ii', $ctstat["usercount"], $ctstat["ctid"]);
    if(false===$stmt2->execute()){
        trigger_error('MySQL-Error: Fehler beim Updaten der CT-Stats: '.$stmt2->error, E_USER_ERROR);
        echo "Could not update stats for CT $ctid.".PHP_EOL;
    }
    $stmt2->close();
}
echo "CT-Besucher Statistiken geupdatet".PHP_EOL;

// Rename old cts so name can be used again
$time5daysago=strtotime('-6 days', strtotime('today midnight'));
$stmt = $db->prepare("UPDATE cts SET name=concat(cts.name, cts.id), archived=1 WHERE archived=0 AND time<?");
$stmt->bind_param('i', $time5daysago);
if(false===$stmt->execute()){
    die("Datenbank-Fehler :c");
}else{
    echo "Alte cts archiviert".PHP_EOL;
}

$endzeit=microtime(true)-$startzeit;
$endzeit=round($endzeit, 3, PHP_ROUND_HALF_UP);
echo "------------------[deinCT-Cron] done at ".date("d.m.Y H:i:s")." after ".($endzeit)." seconds\n";
echo "--------------------------------------------------------------------------------------------------------------------------------".PHP_EOL;
myLog("updater",$successcounterall." Users fetched. ".$updatedcounter."/".($useranzahl)." updated. Took ".$endzeit." seconds.");
$db->close();
?>