<?php
include_once('config.inc.php');
if(!isset($_GET["id"])){//if user opens ctanmeldung.php directly
    header("Location: /index");
    exit();
}
needslogin(); //---------------
@$db = new mysqli($MYSQL_HOST, $MYSQL_USER, $MYSQL_PW, $MYSQL_DB);

$stmt2 = $db->prepare("SELECT id,name,creator,time,picenabled FROM cts WHERE name=?");
$stmt2->bind_param('s', $_GET["id"]);
if(false===$stmt2->execute()){
    //error
    //header("Location: http://ctde.tk/");
    mylog("dberror", "Fetching ct info ctanmeldung.php: ".$stmt2->error);
    die("Datenbank-Fehler :c");
}
$stmt2->bind_result($thisctid,$thisctname,$thisctcreator,$thiscttime,$thispicenabled);
$stmt2->store_result();
$stmt2rows = $stmt2->num_rows;
$stmt2->fetch();
$stmt2->close();
if($stmt2rows < 1){//CT exisitiert nicht
    header("Location: /index");
}

$ctdone=false;
$cttoday=false;
$ctyesterday=false;
if ($thiscttime < time()){
    $ctdone=true;
}
if (date("d.m.Y", time()) == date("d.m.Y", $thiscttime)){
    $cttoday=true;
}
if (date("d.m.Y", time()-86400) == date("d.m.Y", $thiscttime)){
    $ctyesterday=true;
}
$stmt = $db->prepare("SELECT ctguests.coming, ctguests.privacy, ctguests.id FROM ctguests LEFT JOIN users ON ctguests.tw_id = users.tw_uid WHERE ctguests.ctid = ? AND ctguests.tw_id = ? LIMIT 1");
$stmt->bind_param('is', $thisctid, $_SESSION["twitter_uid"]);
if(false===$stmt->execute()){
    mylog("dberror", "Fetching infos already registered ctanmeldung.php: ".$stmt->error);
    die("Datenbank-Fehler :c");
}
$stmt->bind_result($guestcoming,$guestprivacy,$guestid);
$stmt->store_result();
$nummerrows = $stmt->num_rows;
$stmt->fetch();
$stmt->close();
if($nummerrows < 1){
    unset($guestcoming);
    unset($guestprivacy);
    unset($guestid);
}

$anmeldeerror=false;
$ctpicuploadenabled=$thispicenabled;


if (isset($_POST["formsend"])) {
    if ((!$ctdone || $cttoday || $ctyesterday) || !$ctpicuploadenabled){
        if (!isset($_POST["privacy"]) && !isset($_POST["coming"])){
            $_POST["privacy"]="public";
            $_POST["coming"]=1;
        }
        //logged in
        $stmt = $db->prepare("SELECT ctid,tw_id FROM ctguests WHERE ctid=? AND tw_id=?");
        $stmt->bind_param('ss', $thisctid, $_SESSION["twitter_uid"]);
        if(false===$stmt->execute()){
            mylog("dberror", "Fetching infos already registered (2) ctanmeldung.php: ".$stmt->error);
            die("Datenbank-Fehler :c");
        }
        $stmt->store_result();
        $numRows = $stmt->num_rows;
        $stmt->fetch();
        $stmt->close();
        if ($numRows > 0){
            //already signed in into event
            if ($_SESSION["twitter_uid"] == $thisctcreator){
                $anmeldeerror=true;
            }else{
                $stmt2 = $db->prepare("UPDATE ctguests SET coming = ?, privacy = ? WHERE id = ?");
                if ($_POST["coming"]=="yes"){
                    $coming=1;
                }elseif($_POST["coming"]=="maybe"){
                    $coming=2;
                }else{
                    $coming=0;
                }
                
                $privacy=1;
                if ($_POST["privacy"] == "private"){
                    $privacy=0;
                }
                $stmt2->bind_param('iii', $coming, $privacy, $guestid);
                if(false===$stmt2->execute()){
                    mylog("dberror", "Updating ctguest ctanmeldung.php: ".$stmt2->error);
                    die("Datenbank-Fehler :c");
                }
                mylog("ctaction", "Updated registration for ct $thisctname ($thisctid)");
                $stmt2->close();
                header("Location: /$thisctname");
            }
        }else{
            //not signed in to event
            $stmt3 = $db->prepare("INSERT INTO ctguests (ctid, tw_id, coming, privacy) VALUES (?, ?, ?, ?)");
            if ($_POST["coming"]=="yes"){
                $coming=1;
            }elseif($_POST["coming"]=="maybe"){
                $coming=2;
            }else{
                $coming=0;
            }
            $myctid=(int)$thisctid;
            $uid=(string)$_SESSION["twitter_uid"];
            $privacy=1;
            if ($_POST["privacy"] == "private"){
                $privacy=0;
            }
            $stmt3->bind_param('isii', $myctid, $uid, $coming, $privacy);

            if(false===$stmt3->execute()){
                mylog("dberror", "Registering for ct $thisctname ($thisctid) ctanmeldung.php: ".$stmt3->error);
                die("Datenbank-Fehler :c");
            }
            mylog("ctaction", "Registered for $thisctname ($thisctid)");
            $stmt3->close();
            header("Location: /$thisctname");
        }
    }
}

htmlOpen('Anmeldung | '.$thisctname);
?>

    <div class="container">

        <div class="row">
            <div class="box">
                <div id="cterror" class="col-lg-12" style="display:none;">
                    <div class="alert alert-danger alert-dismissible" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
  <strong>Oh Verdammt!</strong> Du darst deine Anmeldung nicht als Veranstalter dieses CTs ändern...
                    </div>
                </div>
                <div id="ctdone" class="col-lg-12" style="display:none;">
                    <div class="alert alert-danger alert-dismissible" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
  <strong>Oh Verdammt!</strong> Dieses CT war bereits. Die Anmeldung ist daher schon geschlossen... <a href="/<?=$thisctname?>">Zurück zum CT</a>
                    </div>
                </div>
                <div class="col-lg-12">
                    <hr>
                    <h2 class="text-center">Zu #<?=$thisctname?> anmelden</h2>
                    <hr>
                </div>
                <div class="col-sm-12 text-center">
                    <form role="form" action="anmelden" method="post">
                        <div class="row">
                            <div class="form-group col-lg-12">
                                <label>Name</label><br>
                                <label id="icon" for="ctuser">
                                    <span class="glyphicon glyphicon-user"></span>
                                </label>
                                <input name="ctuser" id="ctuser" type="text" value="<?php echo $_SESSION['twitter_id']; ?>" disabled>
                            </div>
                            <hr>
                            <div class="form-group col-lg-12">
                                <label>Ich komme zum CT</label>
                                <div id="doodleradios">
                                    <input type="radio" value="yes" id="radComingYes" name="coming" <?php if(isset($guestcoming) && $guestcoming == 1){echo "checked";}else{echo "checked";}?>>
                                    <label for="radComingYes"  class="radio">Ja</label>
                                    <input type="radio" value="maybe" id="radComingMaybe" name="coming" <?php if(isset($guestcoming) && $guestcoming == 2){echo "checked";}?>>
                                    <label for="radComingMaybe"  class="radio">Vielleicht</label>
                                </div>
                                <hr>
                            </div>

                            <div class="form-group col-lg-12">
                                <label>Anmeldung öffentlich anzeigen</label>
                                <div id="privacyradios">
                                    <input type="radio" value="public" id="radPrivacyYes" name="privacy" <?php if(isset($guestprivacy) && $guestprivacy == 1){echo "checked";}else{echo "checked";}?>>
                                    <label for="radPrivacyYes" class="radio">Ja</label>
                                    <input type="radio" value="private" id="radPrivacyNo" name="privacy" <?php if(isset($guestprivacy) && $guestprivacy == 0){echo "checked";}?>>
                                    <label for="radPrivacyNo" class="radio">Nein</label>
                                </div>
                                <hr>
                            </div>
                            <div class="form-group col-lg-12 text-center">
                                <input type="hidden" name="formsend" value="yes">
                                <button id="realsubmitter" type="submit" style="display:none;"></button>
                                <a id="buttonsub" class="button" onclick="$('#realsubmitter').click();"><?php if(isset($guestcoming)){ echo "&Auml;ndern";}else{echo "Anmelden";}?></a>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="clearfix"></div>

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

<?php
genFooter();
genAnalytics();

//echo "<!-- CTDONE:".($ctdone?"True":"False")." CTTODAY:".($cttoday?"True":"False")." CTYESTERDAY:".($ctyesterday?"True":"False")." -->"; //DEBUG
if($anmeldeerror){
    echo '<script>$("#cterror").show();</script>';
}
if($ctdone && !($cttoday) && $ctpicuploadenabled){
    if(!$ctyesterday){ //only show, that the registration is closed the 2nd day after the ct
        echo '<script>$("#ctdone").show();</script>';
    }
}
?>

<?php
genFooter(1);
$db->close();
?>