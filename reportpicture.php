<?php
include_once('config.inc.php');
if(!isset($_GET["id"])){//when no ctid is given
    header("Location: /index");
    exit();
}
//Check if logged in
needslogin(); //---------------
// end check
@$db = new mysqli($MYSQL_HOST, $MYSQL_USER, $MYSQL_PW, $MYSQL_DB);

$stmt1 = $db->prepare("SELECT id,name,creator,time,picenabled FROM cts WHERE name=?");
$stmt1->bind_param('s', $_GET["id"]);
if(false===$stmt1->execute()){
    //error
    //header("Location: http://ctde.tk/");
    mylog("dberror", "Fetching ct info reportpicture.php: ".$stmt1->error);
    die("Datenbank-Fehler :c");
}
$stmt1->bind_result($thisctid,$thisctname,$thisctcreator,$thiscttime,$thisctpicenabled);
$stmt1->store_result();
$stmt1rows = $stmt1->num_rows;
$stmt1->fetch();
$stmt1->close();
$ctdone=$cttoday=false;
if($stmt1rows < 1){//CT exisitiert nicht
    header("Location: /index");
}else{
    if ($thiscttime < time()){
        $ctdone=true;
        if(date("d.m.Y", time()) == date("d.m.Y", $thiscttime)){
            $cttoday=true;
        }else{
            $cttoday=false;
        }
    }else{
        $ctdone=false;
        if(date("d.m.Y", time()) == date("d.m.Y", $thiscttime)){
            $cttoday=true;
        }else{
            $cttoday=false;
        }
    }
}
if(!($cttoday || $ctdone)){
    header("Location: /".$thisctname);
}
if(!$thisctpicenabled){
    header("Location: /".$thisctname);
}

$stmt2 = $db->prepare("SELECT ctguests.coming FROM ctguests WHERE ctguests.ctid = ? AND ctguests.tw_id = ? LIMIT 1");
$stmt2->bind_param('is', $thisctid, $_SESSION["twitter_uid"]);
if(false===$stmt2->execute()){
    mylog("dberror", "Fetching if user registered for event reportpicture.php: ".$stmt2->error);
    die("Datenbank-Fehler :c");
}
$stmt2->bind_result($usercoming);
$stmt2->fetch();
$stmt2->close();
if($usercoming === null ){
    header("Location: /".$thisctname);
}

//Maybe we should really get some data about the picture, like the user who uploaded it, to make the report clearer.
//But do NOT send this data to the report table, bc users could edit this!!
$stmt3 = $db->prepare("SELECT ctphotos.id, ctphotos.picid, ctphotos.filename, ctphotos.uploader FROM ctphotos WHERE ctphotos.ctid = ? AND ctphotos.picid=? LIMIT 1");
$stmt3->bind_param('is', $thisctid, $_GET["picid"]);
if(false===$stmt3->execute()){
    mylog("dberror", "Fetching photo info reportpicture.php: ".$stmt3->error);
    die("Datenbank-Fehler :c");
}
$stmt3->bind_result($rPicRealID, $rPicID, $rPicFilename, $rPicOwnerID);
$stmt3->fetch();
$stmt3->close();


htmlOpen('Bild Melden | '.$thisctname);

?>
    <div class="container">

        <div class="row">
            <div class="box">
                <div id="reportsend" class="col-lg-12" style="display:none;">
                    <div class="alert alert-success alert-dismissible" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
  <strong>Super!</strong> Deine Meldung wurde erfolgreich übermittelt. Wir kümmern und schnellsten darum. Solltest du eine Email-Adresse angegeben haben (oder <a href="https://twitter.com/CTDeutschland">@CTDeutschland auf Twitter folgen</a> bzw du DMs von allen zulässt) werden wir uns nach Abschluss dieses Problems bei dir melden.<br>
  <center><a href="/<?=$thisctname?>">Zurück zum CT</a></center>
                    </div>
                </div>

<?php

if (isset($_GET["report"]) && isset($_GET["picid"]) && $_GET["report"]=="true"){
    if(isset($_POST) && $_SERVER['REQUEST_METHOD'] == "POST"){
        $formerror=array();
        //Report form was submitted
        if ($_POST["reason"]=="0"){
            $formerror[]="Bitte wähle einen Grund aus!";
        }
        if ($_POST["message"]==""){
            $formerror[]="Bitte gebe eine kurze Beschreibung deines Problems an.";
        }
        switch($_POST["reason"]){
            case '1':
                $reason="Unangebrachtes Bild";
                break;
            case '2':
                $reason="Ich bin auf diesem Bild und möchte das nicht";
                break;
            case '3':
                $reason="Pornografischer Inhalt";
                break;
            case '4':
                $reason="Copyright";
                break;
            default:
                $reason="Anderer Grund";
        }

        if(empty($formerror)){
            $stmtX = $db->prepare("INSERT INTO picreports (sender,time,picid,ctid,reason,message) VALUES (?,?,?,?,?,?)");
            $stmtX->bind_param("siiiss", $_SESSION["twitter_uid"], $time, $rPicRealID, $thisctid, $reason, $_POST["message"]);
            $time=time();
            if(!$stmtX->execute()){
                //error
                //die("Error: ".$stmtX->error);
                $formerror[]="Ihre Meldung konnte nicht gesendet werden. Bitte probieren sie es später erneut.";
                $stmtX=null;
            }else{
                $stmtX->close();
                //success message
            }
        }
    }?>

                <div id="reporterror" class="col-lg-12" style="display:none;">
                    <div class="alert alert-danger alert-dismissible" role="alert">
                      <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                      <strong>Oh...</strong> Es sind folgende Fehler aufgetreten:
                      <ul>
                      <?php
                      foreach ($formerror as $myerror) {
                          echo '<li>'.$myerror.'</li>'."\n";
                      }
                      ?>
                      </ul>
                    </div>
                </div>
        <div class="col-sm-12 text-center" id="reportform">
            <form role="form" action="melden" method="post">
                        <div class="row">
                            <div class="col-lg-12">
                                <hr>
                                <h2 class="text-center">Bild melden</h2>
                                <hr>
                            </div>
                            <div class="col-lg-12 text-center">
                                <img src="/<?=$thisctname?>/t/<?=$rPicID?>.jpg">
                                <hr>
                            </div>
                            <div class="form-group col-lg-12">
                                <label for="sel1">Grund:</label>
                                <select class="form-control" id="sel1" name="reason">
                                    <option value="0">Bitte auswählen</option>
                                    <option value="1">Unangebrachtes Bild</option>
                                    <option value="2">Ich bin auf diesem Bild und möchte das nicht</option>
                                    <option value="3">Pornografischer Inhalt</option>
                                    <option value="4">Das ist mein Bild (Copyright)</option>
                                    <option value="5">Sonstiges (unten angeben)</option>
                                </select>
                            </div>
                            <div class="form-group col-lg-12">
                                <label for="message">Nachricht (kurze Beschreibung des Problems):</label>
                                <textarea class="form-control" rows="5" id="message" name="message" required></textarea>
                            </div>
                            <div class="form-group col-lg-12 text-center">
                                <input type="hidden" name="formsend" value="yes">
                                <button id="realsubmitter" type="submit" style="display:none;"></button>
                                <a id="buttonsub" class="button" onclick="$('#realsubmitter').click();">Melden</a>
                            </div>
                        </div>
                    </form>
    
        </div>
    
        <!-- Show form -->
    <?php 
}

?>
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
    <!-- /.container -->

<?php
genFooter();
genAnalytics();

if(isset($_POST) && $_SERVER['REQUEST_METHOD'] == "POST" && empty($formerror)){
    echo '<script>$("#reportform").hide();</script>';
    echo '<script>$("#reportsend").show();</script>';
}
if(isset($_POST) && $_SERVER['REQUEST_METHOD'] == "POST" && !empty($formerror)){
    echo '<script>$("#reporterror").show();</script>';
}
?>

<?php
genFooter(1);
$db->close();
?>