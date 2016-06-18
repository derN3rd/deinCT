<?php
include_once('config.inc.php');
needslogin();
if (!isset($_GET["id"])){
    header("Location: /profil");
}


@$db = new mysqli($MYSQL_HOST, $MYSQL_USER, $MYSQL_PW, $MYSQL_DB);
if (mysqli_connect_errno()) {
    mylog("dberror", "Connection error newct.php: ".mysqli_connect_error());
    die("Datenbank-Fehler :c");
}
$stmt = $db->prepare("SELECT approved,email,tw_uid FROM users WHERE tw_uid = ? LIMIT 1");
$stmt->bind_param('s', $_SESSION['twitter_uid']);
if(false===$stmt->execute()){
    mylog("dberror", "Fetching approved status approveuser.php: ".$stmt->error);
    die("Datenbank-Fehler :c");
}
$stmt->bind_result($isuserapproved,$user_email,$user_id);
$stmt->store_result();
$numRows = $stmt->num_rows;
$stmt->fetch();
$stmt->close();
if ($numRows < 1){//user is logged in, but not in db. dafuq?
    header("Location: /index");
}else{
    if ($isuserapproved == 1){ //user is already approved
        header("Location: /profil");
    }//else -> everthing is fine (user isnt approved)
}

$formerror = array();
$formsuccess = array();


if($_GET["id"] == md5($user_id.$user_email.$user_id)){
    $stmt2 = $db->prepare("UPDATE users SET approved = 1 WHERE tw_uid = ?");
    $stmt2->bind_param('s', $_SESSION["twitter_uid"]);
    if(false===$stmt2->execute()){
        mylog("dberror", "Updating approved approveuser.php: ".$stmt2->error);
        die("Datenbank-Fehler :c");
    }else{
        mylog("user", "Approved via email");
        $formsuccess[]='Dein Account wurde erfolgreich freigeschaltet! <a href="/profil">Klicke hier um auf dein Profil zu kommen</a>';
    }
}else{
    $formerror[]='Deine E-Mail Adresse konnte nicht bestätigt werden. Bitte überprüfe den Link und/oder <a href="/profil/email">lass dir eine neue E-Mail zusenden</a>.';
}

htmlOpen('Account freischalten');
?>

    <div class="container">

        <div class="row">
            <div class="box">
                <div class="col-lg-12">
                    <hr>
                    <h2 class="text-center">E-Mail Adresse bestätigen</h2>
                    <hr>
                </div>
<?php if(!empty($formerror)){
    foreach ($formerror as $einerror){
        echo '<div id="mailerror" class="col-lg-12"><div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Ehm...</strong> '.$einerror.'</div></div>';
    }
}
if(!empty($formsuccess)){
    foreach ($formsuccess as $einsuccess){
        echo '<div id="mailsuccess" class="col-lg-12"><div class="alert alert-success alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Yeay!</strong> '.$einsuccess.'</div></div>';
    }
}

?>
            </div>
            <div class="box">
                <div class="col-lg-12 text-center"><script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<!-- deinCT responsive -->
<ins class="adsbygoogle"
     style="display:block"
     data-ad-client="ca-pub-1630483756799991"
     data-ad-slot="4573892661"
     data-ad-format="auto"></ins>
<script>
(adsbygoogle = window.adsbygoogle || []).push({});
</script>
                <div class="clearfix"></div></div>
            </div>
        </div>

    </div>
    <!-- /.container -->


<?php
genFooter();
?>

<?php
genFooter(1);
?>