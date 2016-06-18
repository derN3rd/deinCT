<?php
include_once('config.inc.php');
needslogin();

@$db = new mysqli($MYSQL_HOST, $MYSQL_USER, $MYSQL_PW, $MYSQL_DB);

if (mysqli_connect_errno()) {
    mylog("dberror", "Connection error deletect.php: ".mysqli_connect_error());
    die("Datenbank-Fehler :c");
}


$stmt = $db->prepare("SELECT approved FROM users WHERE tw_uid = ? LIMIT 1");
$stmt->bind_param('s', $_SESSION['twitter_uid']);
if(false===$stmt->execute()){
    mylog("dberror", "Fetching approved status deletect.php: ".$stmt->error);
    die("Datenbank-Fehler :c");
}
$stmt->bind_result($isuserapproved);
$stmt->store_result();
$numRows = $stmt->num_rows;
$stmt->fetch();
$stmt->close();
if ($numRows < 1){//user is logged in, but not in db. dafuq?
    header("Location: /index");
}else{
    if ($isuserapproved != 1){
        header("Location: /profil?notapproved=true");
    }//else -> everthing is fine
}

$stmt = $db->prepare("SELECT id,name,creator FROM cts WHERE creator = ? AND name = ? LIMIT 1");
$stmt->bind_param('ss', $_SESSION['twitter_uid'], $_GET["id"]);
if(false===$stmt->execute()){
    mylog("dberror", "Fetching ct info deletect.php: ".$stmt->error);
    die("Datenbank-Fehler :c");
}
$stmt->bind_result($ctid,$ctname,$ctcreator);
$stmt->store_result();
$numRows = $stmt->num_rows;
$stmt->fetch();
$stmt->close();
if ($numRows < 1){//ct does not exist or isnt ours
    header("Location: /profil");
}

if (isset($_POST["reallysure"])){ //editct.php delete form was send
    $stmt = $db->prepare("DELETE FROM ctguests WHERE ctid = ?");
    $stmt->bind_param('i', $ctid);
    if (false===$stmt->execute()){
        mylog("dberror", "Deleting ctguests from ct $ctid deletect.php: ".$stmt->error);
        die("Datenbank-Fehler :c");
    }
    $stmt->close();

    $stmt = $db->prepare("DELETE FROM ctphotos WHERE ctid = ?");
    $stmt->bind_param('i', $ctid);
    if (false===$stmt->execute()){
        mylog("dberror", "Deleting ctphotos for ct $ctid deletect.php: ".$stmt->error);
        die("Datenbank-Fehler :c");
    }
    $stmt->close();

    $stmt = $db->prepare("DELETE FROM cts WHERE id = ?");
    $stmt->bind_param('i', $ctid);
    if (false===$stmt->execute()){
        mylog("dberror", "Deleting ct $ctid deletect.php: ".$stmt->error);
        die("Datenbank-Fehler :c");
    }
    $stmt->close();

    $stmt = $db->prepare("DELETE FROM ctstats WHERE ctid = ?");
    $stmt->bind_param('i', $ctid);
    if (false===$stmt->execute()){
        mylog("dberror", "Deleting ctstats for ct $ctid deletect.php: ".$stmt->error);
        die("Datenbank-Fehler :c");
    }
    $stmt->close();


    mylog("ct", "CT $ctname ($ctid) deleted");
    header("Location: /profil");
}

$db->close();
?>