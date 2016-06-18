<?php
include_once('config.inc.php');
needslogin();
@$db = new mysqli($MYSQL_HOST, $MYSQL_USER, $MYSQL_PW, $MYSQL_DB);

htmlOpen('Profil');
?>

    <div class="container">

        <div class="row">
            <div class="box">
                <div class="col-lg-12">
                <?php if($showfeedbackbutton):?><div class="ribbon"><span><a href="/feedback">Feedback</a></span></div><?php endif;?>
                    <hr>
                    <h2 class="text-center">Mein Profil
                    </h2>
                    <hr>
                </div>
<?php
if (mysqli_connect_errno()) {
    //printf("Datenbank Fehler\n", mysqli_connect_error());
    echo '<div class="col-lg-12 text-center"><h2>Datenbank-Fehler :c</h2></div>';
    mylog("dberror", "Connection error profil.php: ".mysqli_connect_error());
    exit();
}
$stmt = $db->prepare("SELECT tw_name,email,approved FROM users WHERE tw_uid = ? LIMIT 1");
$stmt->bind_param('s', $_SESSION['twitter_uid']);
if(false===$stmt->execute()){
    mylog("dberror", "Fetching ct info profil.php(2): ".$stmt->error);
    die("Datenbank-Fehler :c");
}
$stmt->bind_result($user_name,$user_email,$user_approved);
$stmt->store_result();
$numRows = $stmt->num_rows;
$stmt->fetch();
$stmt->close();
?>
                <div id="emailerror" class="col-lg-12" style="display:none;">
                    <div class="alert alert-danger alert-dismissible" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
  <strong>Schade...</strong> Deine E-Mail-Adresse konnte nich bestätigt werden. Entweder ist dein Account bereits bestätigt oder der Link war defekt. <a href="#">Link erneut anfordern.</a></div>
                </div>
                <div id="emailsuccess" class="col-lg-12" style="display:none;">
                    <div class="alert alert-success alert-dismissible" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
  <strong>Yeay!</strong> Deine E-Mail-Adresse wurde erfolgreich bestätigt.</div>
                </div>
                <div id="notapproved" class="col-lg-12" style="display:none;">
                    <div class="alert alert-info alert-dismissible" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
  <strong>Hm...</strong> Dein Account ist leider noch nicht bestätigt!</div>
                </div>
                <div class="col-sm-12 text-left">
                <img src="<?=$_SESSION["twitter_pic"]?>" style="float:left; margin-right:10px;">
                    <p>Username: @<?=$user_name?><br>
                    E-Mail: <?php if($user_approved==1){echo $user_email;}else{ echo 'Keine Angabe (<a href="/profil/email">Jetzt bestätigen und Account freischalten</a>)';}?>
                    </p>
                    <hr>
                </div>
                <div class="col-sm-6 text-left">
                    <h3 class="text-center">Eigene CTs</h3>
                    <hr><?php if($user_approved==1){echo '<div class="text-center"><a href="cts/neu" class="btn btn-default btn-sm"> Neues CT erstellen</a></div><br>';}?>
                    <div class="table-responsive">
                    <table style="width:100%" class="table table-striped table-bordered table-hover text-center">
<?php
if ($resultat = $db->query("SELECT name, creator, time FROM cts WHERE creator = ".(($_SESSION["twitter_uid"]==null)?'NULL':$_SESSION["twitter_uid"])." ORDER BY time DESC")) {
    for ($res = array(); $tmp = $resultat->fetch_array(MYSQLI_ASSOC);){
        $res[] = $tmp;
        echo '<tr><td class="text-left"><a href="/'.$tmp["name"].'">'.$tmp["name"].'</a></td><td><small>'.date("d.m.Y H:i",$tmp["time"]).'</small></td><td class="text-right"><a href="/'.$tmp["name"].'/bearbeiten"><span class="glyphicon glyphicon-pencil"></span></a></td></tr>';
    }
    if (sizeof($res) < 1 || $res == array()){
        echo '<tr><td class="text-center">Du hast noch keine CTs geplant!</td></tr>';
    }
    $resultat->close();
}else{
    echo '<tr><td class="text-center">Datenbank-Fehler :c</td></tr>';
    mylog("dberror", "Fetching list of own cts profil.php: ".mysqli_error($db));
}//mysql
?>
                    </table>
                    </div>
                </div>
                <div class="col-sm-6 text-left">
                    <hr class="visible-xs">
                    <h3 class="text-center">Angemeldete Cts</h3>
                    <hr>
                    <div class="table-responsive">
                    <table style="width:100%" class="table table-striped table-bordered table-hover text-center">
<?php
if ($resultat = $db->query("SELECT cts.name, cts.time, cts.id, ctguests.ctid FROM cts LEFT JOIN ctguests ON cts.id=ctguests.ctid WHERE ctguests.tw_id = ".(($_SESSION["twitter_uid"]==null)?'NULL':$_SESSION["twitter_uid"])." ORDER BY cts.time DESC")) {
    for ($res = array(); $tmp = $resultat->fetch_array(MYSQLI_ASSOC);){
        $res[] = $tmp;
        echo '<tr><td><a href="/'.$tmp["name"].'">'.$tmp["name"].'</a></td><td><small>'.date("d.m.Y H:i",$tmp["time"]).'</small></td></tr>';
    }
    if (sizeof($res) < 1 || $res == array()){
        echo '<tr><td class="text-center">Du bist in keinen CTs angemeldet.</td></tr>';
    }
    $resultat->close();
}else{
    echo '<tr><td class="text-center">Datenbank-Fehler :c</td></tr>';
    mylog("dberror", "Fetching list of joined cts profil.php: ".mysqli_error($db));
}//mysql
?>
                    </table>
                    </div>
                </div>
                <div class="clearfix"></div>

            </div>
        </div>
        <div class="row">
            <div class="box">
                <div class="col-lg-12">
                    <script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<!-- deinCT responsive -->
<ins class="adsbygoogle"
     style="display:block"
     data-ad-client="ca-pub-1630483756799991"
     data-ad-slot="4573892661"
     data-ad-format="auto"></ins>
<script>
(adsbygoogle = window.adsbygoogle || []).push({});
</script>
                </div>
                <div class="clearfix"></div>
            </div>
        </div>

    </div>
    <!-- /.container -->


<?php
genFooter();
genAnalytics();
if (isset($_GET["notapproved"]) && $_GET["notapproved"]=="true"){
    echo '<script>$("#notapproved").show();</script>';
}
genFooter(1);
$db->close();
?>