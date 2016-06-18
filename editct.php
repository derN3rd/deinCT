<?php
include_once('config.inc.php');
require_once('inc/library/HTMLPurifier.auto.php');
require('inc/ReCaptcha.autoload.php');
needslogin();
@$db = new mysqli($MYSQL_HOST, $MYSQL_USER, $MYSQL_PW, $MYSQL_DB);
if (mysqli_connect_errno()) {
    mylog("dberror", "Connection error editct.php: ".mysqli_connect_error());
    die("Datenbank-Fehler :c");
}
function isValid($str) {
    return !preg_match('/[^A-Za-z0-9äÄöÖüÜß]/', $str);
}
$stmt = $db->prepare("SELECT approved FROM users WHERE tw_uid = ? LIMIT 1");
$stmt->bind_param('s', $_SESSION['twitter_uid']);
if(false===$stmt->execute()){
    mylog("dberror", "Fetching approved status editct.php: ".$stmt->error);
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

$stmt = $db->prepare("SELECT id,name,creator,description,time,place,public,picenabled FROM cts WHERE creator = ? AND name = ? LIMIT 1");
$stmt->bind_param('ss', $_SESSION['twitter_uid'], $_GET["id"]);
if(false===$stmt->execute()){
    mylog("dberror", "Fetching ct data editct.php: ".$stmt->error);
    die("Datenbank-Fehler :c");
}
$stmt->bind_result($ctid,$ctname,$ctcreator,$ctdescription,$cttime,$ctplace,$ctpublic,$ctpicenabled);
$stmt->store_result();
$numRows = $stmt->num_rows;
$stmt->fetch();
$stmt->close();
if ($numRows < 1){//ct does not exist
    header("Location: /profil");
}
$cttime=date("d.m.Y H:i",$cttime);


$formerror = array();
if (isset($_POST["formsend"])){ //editct.php form was send
    if ( ($_POST["ctname"] != "") &&
    ($_POST["description"] != "") &&
    (isset($_POST["checkagb"])) &&
    (isset($_POST["privacy"])) &&
    (isset($_POST["pictures"])) &&
    ($_POST["cttime"] != "") &&
    ($_POST["ctplace"] != "") ){
        $diderror=false;
        $recaptcha = new \ReCaptcha\ReCaptcha($RECAPTCHA_NEWCT_PRIVATE);
        $resp = $recaptcha->verify($_POST["g-recaptcha-response"], $_SERVER['REMOTE_ADDR']);
        if ($resp->isSuccess()) {
            $diderror=false;
        } else {
            $diderror=true;
            //$errors = $resp->getErrorCodes();
            $formerror[] = "Captcha fehlerhaft!";
        }
        if (!isValid($_POST["ctname"])){
            $diderror=true;
            $formerror[] = "Bitte verwende nur Buchstaben und Zahlen im CT Namen.";
        }

        if ($_POST["cttime"] == "__.__.____ __:__"){
            $diderror=true;
            $formerror[] = "Bitte gib ein richtiges Datum ein!";
        }

        if (strlen(utf8_decode($_POST["ctname"])) > 25){
        	$diderror=true;
        	$formerror[] = "Bitte beachte, dass der CTName maximal 25 Zeichen lang sein darf!";
        }

        if(!$diderror){
            //CTName and description are set. acceptterms is checked
            $puriconfig = HTMLPurifier_Config::createDefault();
            //$puriconfig->set('Core.Encoding', 'ISO-8859-1');
            $puriconfig->set('HTML.Allowed', 'b,i,k,br,st');
            $purifier = new HTMLPurifier($puriconfig);
            $safe_desc = $purifier->purify(str_replace(PHP_EOL,"<br>",$_POST["description"]));

            $eventtime=strtotime($_POST["cttime"]);

            $picenabled=0;
            if($_POST["pictures"]=="yes"){
                $picenabled=1;
            }

            if($_POST["privacy"]=="private"){
                $eventprivacy=0;
            }else{
                $eventprivacy=1;
            }
            $stmt = $db->prepare("SELECT name FROM cts WHERE name = ? ORDER BY time ASC");
            $stmt->bind_param('s', $_POST["ctname"]);
            if(false===$stmt->execute()){
                mylog("dberror", "Fetching ctname editct.php: ".$stmt->error);
                die("Datenbank-Fehler :c");
            }
            $stmt->bind_result($notusedctname);
            $stmt->store_result();
            $numRows = $stmt->num_rows;
            $stmt->fetch();
            $stmt->close();
            if ($numRows > 0 && $notusedctname != $ctname){
                //name vergeben
                $diderror=true;
                $formerror[] = "Der ausgew&auml;hlte CT-Name ist bereits vergeben. Bitte w&auml;hle einen anderen.";
            }else{
                $stmt2 = $db->prepare("UPDATE cts SET name = ?, creator = ?, description = ?, time = ?, place = ?, public = ?, picenabled = ? WHERE id = ?");
                $stmt2->bind_param('sssisiii', $_POST["ctname"], $_SESSION["twitter_uid"], $_POST["description"], $eventtime, $_POST["ctplace"], $eventprivacy, $picenabled, $ctid);
                if(false===$stmt2->execute()){
                    mylog("dberror", "Updating ct editct.php: ".$stmt2->error);
                    die("Datenbank-Fehler :c");
                }else{
                    mylog("ct", "CT $ctid updated");
                    header("Location: /".$_POST["ctname"]);
                }
                $stmt2->close();
            }
        }else{//error did happen while doing checks
            //dunno what i could do here next lel
        }
    }else{//not everthing was filled out right
        $diderror=true;
        $formerror[] = "Es wurde nicht alles korrekt ausgef&uuml;llt! Bitte &uuml;berpr&uuml;fe deine Eingaben.";
    }
}

htmlOpen('CT Bearbeiten');
?>

    <div class="container">

        <div class="row">
            <div class="box">
                <div class="col-lg-12">
                    <hr>
                    <h2 class="text-center">CT bearbeiten</h2>
                    <hr>
                </div>
<?php if(!empty($formerror)){
    foreach ($formerror as $einerror){
        echo '<div id="cterror" class="col-lg-12"><div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Ehm...</strong> '.$einerror.'</div></div>';
    }
}?>
                <div class="col-sm-12 text-center">
                    <form role="form" action="bearbeiten" method="post" accept-charset="utf-8">
                        <div class="row">
                            <div class="form-group col-lg-12">
                                <label>CT Name</label><br>
                                <small>(Ohne # am Anfang, nur A-Z und Zahlen, keine Leer-/Sonderzeichen)</small><br>
                                <label id="icon" for="ctname">
                                    <span class="glyphicon glyphicon-eye-open"></span>
                                </label>
                                <input name="ctname" id="ctname" type="text" maxlength="25" <?php if(isset($_POST["ctname"])){echo 'value="'.$_POST["ctname"].'"';}else{echo 'value="'.$ctname.'"';}?> required>
                                <hr>
                            </div>
                            <div class="form-group col-lg-12">
                                <label>Beschreibung</label><br>
                                <small>(Erlaubte Formatierungs-Zeichen sind &lt;b&gt;<b>fett</b>&lt;/b&gt;, &lt;i&gt;<i>kursiv</i>&lt;/i&gt; und &lt;u&gt;<u>unterstrichen</u>&lt;/u&gt;)</small>
                                <textarea name="description" class="form-control text-center" rows="6" maxlength="2048" required><?php if(isset($_POST["description"])){echo $_POST["description"];}else{echo $ctdescription;}?></textarea>
                                <hr>
                            </div>
                            <div class="form-group col-lg-12" id="timeinput">
                                <label>Datum</label><br>
                                <label id="icon" for="cttime">
                                    <i class="glyphicon glyphicon-time"></i>
                                </label>
                                <input type="text" name="cttime" id="cttime" <?php if(isset($_POST["cttime"])){echo 'value="'.$_POST["cttime"].'"';}else{echo 'value="'.$cttime.'"';}?> required>
                                <hr>
                            </div>
                            <div class="form-group col-lg-12">
                                <label>Ort</label><br>
                                <label id="icon" for="ctname">
                                    <i class="glyphicon glyphicon-globe"></i>
                                </label>
                                <input type="text" id="ctplace" name="ctplace" <?php if(isset($_POST["ctplace"])){echo 'value="'.$_POST["ctplace"].'"';}else{echo 'value="'.$ctplace.'"';}?> required>
                                <hr>
                            </div>
                            <div class="form-group col-lg-12">
                                <label>CT öffentlich in der Liste anzeigen?</label>
                                <div id="privacyradios">
                                    <input type="radio" value="public" id="radprivacyOne" name="privacy" <?php
            if(isset($_POST["privacy"])){
                if($_POST["privacy"]=="public"){echo 'checked';}
            }else{
                if($ctpublic==1){echo 'checked';}
            }?>>
                                    <label for="radprivacyOne" class="radio">Ja</label>
                                    <input type="radio" value="private" id="radprivacyTwo" name="privacy" <?php
            if(isset($_POST["privacy"])){
                if($_POST["privacy"]=="private"){echo 'checked';}
            }else{
                if($ctpublic==0){echo 'checked';}
            }?>>
                                    <label for="radprivacyTwo"  class="radio">Nein</label>
                                </div>
                                <hr>
                            </div>
                            <div class="form-group col-lg-12">
                                <label>Hochladen von Bildern aktivieren?<br><small>Nach dem CT können so Bilder hochgeladen werden, die von allen Teilnehmern einsehbar sind.</small></label>
                                <div id="picradios">
                                    <input type="radio" value="yes" id="radpicOne" name="pictures" <?php
            if(isset($_POST["pictures"])){
                if($_POST["pictures"]=="yes"){echo 'checked';}
            }else{
                if($ctpicenabled){echo 'checked';}
            }?>>
                                    <label for="radpicOne"  class="radio">Ja</label>
                                    <input type="radio" value="no" id="radpicTwo" name="pictures" <?php
            if(isset($_POST["pictures"])){
                if($_POST["pictures"]=="no"){echo 'checked';}
            }else{
                if(!$ctpicenabled){echo 'checked';}
            }?>>
                                    <label for="radpicTwo"  class="radio">Nein</label>
                                </div>
                                <hr>
                            </div>
                            <div class="form-goup col-lg-12">
                                <input name="checkagb" id="checkagb" type="checkbox" required>
                                <label for="checkagb" class="checkbox">Ich habe die AGB gelesen und aktzeptiere sie.</label>
                                <hr>
                            </div>
                            <div class="form-group col-lg-12 text-center">
                                <div class="g-recaptcha" data-sitekey="<?php echo $RECAPTCHA_NEWCT_PUBLIC;?>"></div>
                            </div>
                            <div class="form-group col-lg-12 text-center">
                            <a type="submit" id="buttonsub" class="button" onclick="$('#submitterb').trigger('click');">CT BEARBEITEN</a>
                                <br>
                                <hr>
                            </div>
                            <div class="form-group col-lg-12 text-center">
                                <p class="text-right" style="font-size: 0.8em;"><button type="button" class="btn btn-default btn-danger" data-toggle="modal" data-target="#dataDeleteCT">CT LÖSCHEN</button></p>
                            </div>
                        </div>
                        <input type="hidden" name="formsend" value="jepp">
                        <button id="submitterb" type="submit" style="display:none;"></button>
                    </form>
                </div>
                <div class="clearfix"></div>

            </div>
        </div>

    </div>
    <!-- /.container -->

    <div class="modal fade" id="dataDeleteCT" tabindex="-1" role="dialog" aria-labelledby="dataDeleteCTLabel">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="myModalLabel">CT LÖSCHEN</h4>
          </div>
          <div class="modal-body">
            <p>Bist du dir sicher, dass du #<?=$ctname?> löschen willst?</p><br>
              <small>Gelöschte CTs können nicht wiederhergestellt werden.<br>Außerdem werden die eingetragenen Gäste <b>NICHT</b> darüber informiert, dass dieses CT gelöscht wurde.</small>
          </div>
          <div class="modal-footer">
              <form method="post" action="/<?=$ctname?>/entfernen">
                  <input type="hidden" name="reallysure" value="yes">
                  <button type="submit" class="btn btn-danger">Löschen</button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">Schließen</button>
              </form>
          </div>
        </div>
      </div>
    </div>


<?php
genFooter();
?>
    <!-- DateTime Picker jQuery by XDSoft -->
    <script src="/js/jquery.datetimepicker.full.min.js"></script>

    <!-- Maps Api Autocomplete -->
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB362bAj7q0-3N62OjBB5QEUQ-2Ht8ZvZY&libraries=places"></script>
    <script src='https://www.google.com/recaptcha/api.js'></script>
    <script>
        //Google Maps Autocomplete Setup
        autocomplete = new google.maps.places.Autocomplete(
            /** @type {!HTMLInputElement} */(document.getElementById('ctplace')),
            {types: ['geocode']});


        //other stuff
        $(document).ready(function() {
            //DateTime Picker setup
            $('#cttime').datetimepicker({
                startDate:'+<?php echo date('d.m.Y H:i');?>',
                minDate:'+<?php echo date('d.m.Y');?>',
                format:'d.m.Y H:i',
                mask:true,
                lang:'de'
            });
        });

    </script>
<?php
genFooter(1);
$db->close();
?>