<?php
include_once('config.inc.php');
require_once('inc/library/HTMLPurifier.auto.php');
require('inc/ReCaptcha.autoload.php');
needslogin();

@$db = new mysqli($MYSQL_HOST, $MYSQL_USER, $MYSQL_PW, $MYSQL_DB);
if (mysqli_connect_errno()) {
    mylog("dberror", "Connection error newct.php: ".mysqli_connect_error());
    die("Datenbank-Fehler :c");
}
function isValid($str) {
    return !preg_match('/[^A-Za-z0-9äÄöÖüÜß]/', $str);
}
$stmt = $db->prepare("SELECT approved FROM users WHERE tw_uid = ? LIMIT 1");
$stmt->bind_param('s', $_SESSION['twitter_uid']);
if(false===$stmt->execute()){
    mylog("dberror", "Fetching approved status newct.php: ".$stmt->error);
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

$formerror = array();

if (isset($_POST["formsend"])){ //newct.php form was send
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
            mylog("usererror", "Entered wrong captcha newct.php");
        }
        if (!isValid($_POST["ctname"])){
            $diderror=true;
            $formerror[] = "Bitte verwende nur Buchstaben und Zahlen im CTNamen.";
            mylog("usererror", "Entered illegal chars in CTName newct.php");
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
            $puriconfig->set('HTML.Allowed', 'b,i,u,br,st');
            $purifier = new HTMLPurifier($puriconfig);
            $safe_desc = $purifier->purify(str_replace(PHP_EOL,"<br>",$_POST["description"]));
            $eventtime=strtotime($_POST["cttime"]);
            $eventdoodle="none";

            $picenabled=0;
            if($_POST["pictures"]=="yes"){
                $picenabled=1;
            }

            $eventprivacy=1;
            if($_POST["privacy"]=="private"){
                $eventprivacy=0;
            }
            $stmt = $db->prepare("SELECT name FROM cts WHERE name = ? ORDER BY time ASC");
            $stmt->bind_param('s', $_POST["ctname"]);
            if(false===$stmt->execute()){
                mylog("dberror", "Fetching/checking ctname newct.php: ".$stmt->error);
                die("Datenbank-Fehler :c");
            }
            $stmt->bind_result($notusedctname);
            $stmt->store_result();
            $numRows = $stmt->num_rows;
            $stmt->fetch();
            $stmt->close();
            if ($numRows > 0){
                //name vergeben
                $diderror=true;
                $formerror[] = "Der ausgew&auml;hlte CT-Name ist bereits vergeben. Bitte w&auml;hle einen anderen.";
            }else{
                $stmt2 = $db->prepare("INSERT INTO cts (name, creator, description, time, place, public, picenabled) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt2->bind_param('sssisii', $_POST["ctname"], $_SESSION["twitter_uid"], $_POST["description"], $eventtime, $_POST["ctplace"], $eventprivacy, $picenabled);
                if(false===$stmt2->execute()){
                    mylog("dberror", "Inserting new ct newct.php: ".$stmt2->error);
                    die("Datenbank-Fehler :c");
                }else{
                    $insertedCTid=$stmt2->insert_id;
                    mkdir("data/photos/".$insertedCTid, 0755);
                    $newCTid = $stmt2->insert_id;
                    mylog("ct", "Created new CT ".$_POST["ctname"]." ($newCTid)");
                    $stmt4 = $db->prepare("INSERT INTO ctguests (ctid, tw_id, coming, privacy) VALUES (?, ?, 1, 1)");
                    $stmt4->bind_param('is', $newCTid, $_SESSION["twitter_uid"]);
                    if(false===$stmt4->execute()){
                        mylog("dberror", "Inserting user to his ct newct.php: ".$stmt4->error);
                        die("Datenbank-Fehler :c");
                    }else{
                        mylog("ctaction", "Forced registration for just created ct ".$_POST["ctname"]." ($newCTid)");
                        $stmt5 = $db->prepare("INSERT INTO ctstats (ctid, current, last) VALUES (?, 1, 1)");
                        $stmt5->bind_param('i', $newCTid);
                        if(false===$stmt5->execute()){
                            mylog("dberror", "Inserting new ctstats newct.php: ".$stmt5->error);
                            die("Datenbank-Fehler :c");
                        }else{
                            header("Location: /".$_POST["ctname"]);
                        }
                    }
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


htmlOpen('Neues CT');
?>

    <div class="container">

        <div class="row">
            <div class="box">
                <div class="col-lg-12">
                    <hr>
                    <h2 class="text-center">Neues CT erstellen</h2>
                    <hr>
                </div>
<?php if(!empty($formerror)){
    foreach ($formerror as $einerror){
        echo '<div id="cterror" class="col-lg-12"><div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Ehm...</strong> '.$einerror.'</div></div>';
    }
}?>
                <div class="col-sm-12 text-center">
                    <form role="form" action="neu" method="post" accept-charset="utf-8">
                        <div class="row">
                            <div class="form-group col-lg-12">
                                <label>CT Name<br>
                                <small>(Ohne # am Anfang, nur A-Z und Zahlen, keine Leer-/Sonderzeichen)</small></label><br>
                                <label id="icon" for="ctname">
                                    <span class="glyphicon glyphicon-eye-open"></span>
                                </label>
                                <input name="ctname" id="ctname" type="text" maxlength="25" placeholder="CTName" <?php if(isset($_POST["ctname"])){echo 'value="'.$_POST["ctname"].'"';}?>  required>
                                <hr>
                            </div>
                            <div class="form-group col-lg-12">
                                <label>Beschreibung<br>
                                <small>(Erlaubte Formatierungs-Zeichen sind &lt;b&gt;<b>fett</b>&lt;/b&gt;, &lt;i&gt;<i>kursiv</i>&lt;/i&gt; und &lt;u&gt;<u>unterstrichen</u>&lt;/u&gt;)</small></label>
                                <textarea name="description" class="form-control text-center" rows="6" maxlength="2048" placeholder="Hier sollte eine aussagekräftige Beschreibung zum CT stehen, z.B. wo genau man sich trifft, was mitgebracht werden soll, usw" required><?php if(isset($_POST["description"])){echo $_POST["description"];}?></textarea>
                                <hr>
                            </div>
                            <div class="form-group col-lg-12" id="timeinput">
                                <label>Datum</label><br>
                                <label id="icon" for="cttime">
                                    <i class="glyphicon glyphicon-time"></i>
                                </label>
                                <input type="text" name="cttime" id="cttime" <?php if(isset($_POST["cttime"])){echo 'value="'.$_POST["cttime"].'"';}?> required>
                                <hr>
                            </div>
                            <div class="form-group col-lg-12">
                                <label>Ort</label><br>
                                <label id="icon" for="ctname">
                                    <i class="glyphicon glyphicon-globe"></i>
                                </label>
                                <input type="text" id="ctplace" name="ctplace" placeholder="Alexanderplatz, Berlin" <?php if(isset($_POST["ctplace"])){echo 'value="'.$_POST["ctplace"].'"';}?> required>
                                <hr>
                            </div>
                            <div class="form-group col-lg-12">
                                <label>CT öffentlich in der Liste anzeigen?</label>
                                <div id="privacyradios">
                                    <input type="radio" value="public" id="radprivacyOne" name="privacy" checked="">
                                    <label for="radprivacyOne" class="radio">Ja</label>
                                    <input type="radio" value="private" id="radprivacyTwo" name="privacy">
                                    <label for="radprivacyTwo"  class="radio">Nein</label>
                                </div>
                                <hr>
                            </div>
                            <div class="form-group col-lg-12">
                                <label>Hochladen von Bildern aktivieren?<br><small>Nach dem CT können so Bilder hochgeladen werden, die von allen Teilnehmern einsehbar sind.</small></label>
                                <div id="picradios">
                                    <input type="radio" value="yes" id="radpicOne" name="pictures" checked="">
                                    <label for="radpicOne"  class="radio">Ja</label>
                                    <input type="radio" value="no" id="radpicTwo" name="pictures">
                                    <label for="radpicTwo"  class="radio">Nein</label>
                                </div>
                                <hr>
                            </div>
                            <div class="form-goup col-lg-12">
                                <input name="checkagb" id="checkagb" type="checkbox" required>
                                <label for="checkagb" class="checkbox">Ich habe die <a href="/agb" target="_blank">AGB gelesen und aktzeptiere sie</a>.</label>
                            </div>
                            <div class="form-group col-lg-12 text-center">
                                <div class="g-recaptcha" data-sitekey="<?php echo $RECAPTCHA_NEWCT_PUBLIC;?>"></div>
                            </div>
                            <div class="form-group col-lg-12 text-center">
                                <a type="submit" id="buttonsub" class="button" onclick="$('#submitterb').trigger('click');">CT ERSTELLEN</a>
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