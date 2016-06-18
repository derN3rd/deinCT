<?php
include_once('config.inc.php');
$startzeit = microtime(true);
if(!isset($_GET["id"])){//if user opens ct.php directly
    header("Location: /index");
    exit();
}
//set up db connection
@$db = new mysqli($MYSQL_HOST, $MYSQL_USER, $MYSQL_PW, $MYSQL_DB);
//check if connection was successfull
if (mysqli_connect_errno()) {
    mylog("dberror", "Connection error ct.php(1): ".mysqli_connect_error());
    die("Datenbank Fehler :c");
}
//fetch all information about the ct
$stmt = $db->prepare("SELECT cts.id,cts.name,cts.creator,cts.description,cts.time,cts.place,users.tw_name,cts.picenabled FROM cts LEFT JOIN users ON cts.creator = users.tw_uid WHERE cts.name = ? LIMIT 1");
$stmt->bind_param('s', $_GET['id']);
if(false===$stmt->execute()){
    mylog("dberror", "Fetching ct info ct.php(2): ".$stmt->error);
    die("Datenbank-Fehler :c");
}
$stmt->bind_result($ctid,$ctname,$ctcreator,$ctdescription,$cttime,$ctplace,$ctmacher,$ctpicenabled);
$stmt->store_result();
$numRows = $stmt->num_rows;
$stmt->fetch();
$stmt->close();


//----------------------------------------------------------------------------------------------------
//registering global vars
$ctdone=false;
$cttoday=false;
$ctyesterday=false;

if ($numRows < 1){
    header("Location: /index");
    exit();
}
if ($cttime < time()){
    $ctdone=true;
}
if (date("d.m.Y", time()) == date("d.m.Y", $cttime)){
    $cttoday=true;
}
if (date("d.m.Y", time()-86400) == date("d.m.Y", $cttime)){
    $ctyesterday=true;
}
$cttimecount=date("F d, Y H:i:s", $cttime);
$cttime=date("d.m.Y H:i", $cttime)." Uhr";
$betterplace=urlencode($ctplace);

htmlOpenCT("#".$ctname,'Weitere Infos zum CT von @'.$ctmacher.' am '.$cttime.'...');
?>

    <div class="container">

        <div class="row">
            <div class="box">
                <div class="col-lg-12">
                    <hr>
                    <h3 class="text-center">#<?=$ctname?></h3>
                    <hr>
                </div>
                <div class="col-sm-12 text-center">
                    <p id="descriptionofct"><?=nl2br2($ctdescription)?></p>
                    <hr>
                </div>
                <div class="col-sm-8 text-center">
                    <div class="google-maps"><iframe width="800" height="450" frameborder="0" style="border:0" src="https://www.google.com/maps/embed/v1/place?key=AIzaSyB362bAj7q0-3N62OjBB5QEUQ-2Ht8ZvZY&amp;q=<?=urlencode($ctplace)?>" allowfullscreen=""></iframe></div>
                </div>
                <div class="col-sm-4 text-center">
                    <hr class="visible-xs">
                    <h3>Mehr Infos</h3>
                    <hr>
                    <p>
                        <u>Wann?</u><br>
                        <?=$cttime?><br>
                        <u>Wo?</u><br>
                        <?=$ctplace?><br>
                        <u>Veranstalter:</u> <a href="https://twitter.com/<?=$ctmacher?>" target="_blank"><?=$ctmacher?></a><br>
                        <u>Teilnehmer:</u> <span id="ctteilnehmer">x</span>
                    </p>
                    <!--<h2 id="ctanbut"><a href="/<?=$ctname?>/anmelden">Zum CT anmelden</a></h2>-->
                    <!--<h3 id="ctchanbut" style="display:none;"><a href="/<?=$ctname?>/anmelden">Anmeldung ändern</a></h3>-->
                    <p id="amiregistered" class="text-center" style="display:none;color:red;font-weight:400;">Du bist für dieses CT eingetragen!</p>
                    <button id="ctanbut" type="button" class="btn btn-info btn-lg" data-toggle="modal" data-target="#anmeldung">Zum CT anmelden</button>
                    <p><br><a class="twitter-share-button" href="https://twitter.com/share" data-size="large" data-url="https://deinct.de/<?=$ctname?>" data-count-url="https://deinct.de/<?=$ctname?>" data-via="CTDeutschland " data-text="Meld dich jetzt zu #<?=$ctname?> an!" data-lang="de"></a></p>
                    <p><script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
                        <!-- deinCT CTPage Right -->
                        <ins class="adsbygoogle"
                             style="display:inline-block;width:234px;height:60px"
                             data-ad-client="ca-pub-1630483756799991"
                             data-ad-slot="2435755465"></ins>
                        <script>
                        (adsbygoogle = window.adsbygoogle || []).push({});
                        </script></p>
                </div>
                <div class="clearfix"></div>

            </div>
            <div class="box">
                <div class="col-lg-12 text-center">
<?php   //---------------------------------------------------------------------------------
$dothecountdown=false;
if($ctdone){
    if(!$cttoday){
        echo "<h1>Dieses CT war bereits!</h1>";
    }else{
        echo '<h1>Dieses CT ist heute ('.$cttime.')!</h1>';
    }
}else{
    echo '<h2 class="countdown"></h2>';
    $dothecountdown=true;
}
//-----------------------------------------------------------------------------------------
?>
                </div>
            </div>
            <div class="box" id="teilnehmer">
                <div class="col-lg-12">
                    <h2 class="text-center">Teilnehmer</h2>
                    <p class="text-center" id="ctcounter">Ingesamt x (n davon privat)</p>
                    <hr>
                </div>
<?php
$generalcounter=$hiddencounter=$yescounter=$maybecounter=0;
$eingetragen=false;
//get ctguests counts for privacy
$stmt = $db->prepare("SELECT privacy, COUNT(*) AS counter FROM ctguests WHERE ctid=? AND coming!=0 GROUP BY privacy");
$stmt->bind_param('i', $ctid);
if(false===$stmt->execute()){
    mylog("dberror", "Fetching ct privacy counts ct.php(3): ".$stmt->error);
    die("Datenbank-Fehler :c");
}
$stmt->bind_result($privtype,$privcount);
while ($stmt->fetch()) {
    if($privtype==0) $hiddencounter=$privcount;
}
$stmt->close();

//get ctguests counts for coming
$stmt2 = $db->prepare("SELECT coming, COUNT(*) AS counter FROM ctguests WHERE ctid=? AND coming!=0 GROUP BY coming");
$stmt2->bind_param('i', $ctid);
if(false===$stmt2->execute()){
    mylog("dberror", "Fetching ct coming counts ct.php(4): ".$stmt2->error);
    die("Datenbank-Fehler :c");
}
$stmt2->bind_result($comtype,$comcount);
while ($stmt2->fetch()) {
    if($comtype==1) $yescounter=$comcount;
    if($comtype==2) $maybecounter=$comcount;
    //if($value["coming"]=="no") $nocounter=$value["counter"]; //maybe add this someday
    $generalcounter = $generalcounter + $comcount;
}
$stmt2->close();

//get ctguests to check if user is registered for this ct
$stmt2 = $db->prepare("SELECT coming, privacy FROM ctguests WHERE ctid=? AND tw_id=? LIMIT 1");
$stmt2->bind_param('is', $ctid, $_SESSION["twitter_uid"]);
if(false===$stmt2->execute()){
    mylog("dberror", "Fetching ct user registration ct.php(5): ".$stmt2->error);
    die("Datenbank-Fehler :c");
}
$stmt2->bind_result($guestcoming, $guestprivacy);
$stmt2->store_result();
$stmt2->fetch();
if($stmt2->num_rows > 0) {
    $eingetragen=true;
}
$stmt2->close();


$ctcounter="Es haben sich insgesamt <b>$generalcounter</b> Personen angemeldet. (<b>$hiddencounter</b> davon als privat.)<br><i><b>$yescounter</b> Personen haben fest zugesagt, während sich <b>$maybecounter</b> noch unsicher sind.</i>";



//TODO: mit prepared statements machen, nicht klassisch mysqli.
$stmtCTG = $db->prepare("SELECT u.tw_name,u.tw_pic,ctg.coming FROM ctguests AS ctg LEFT JOIN users AS u ON ctg.tw_id = u.tw_uid WHERE ctg.privacy=1 AND ctg.ctid=? ORDER BY ctg.id DESC LIMIT 6");
$stmtCTG->bind_param('i', $ctid);
if(false===$stmtCTG->execute()){
    mylog("dberror", "Fetching ct guests ct.php(5): ".$stmtCTG->error);
    echo '<div class="col-sm-12 text-center"><p>Datenbank Fehler :c</p></div>';
}
$stmtCTGres=$stmtCTG->get_result();
$stmtCTGdata=$stmtCTGres->fetch_all();
foreach ($stmtCTGdata as $guest) {
    echo '<div class="col-sm-2 col-xs-4 text-center">';
    echo '<div class="userimg"><a target="_blank" href="https://twitter.com/'.$guest[0].'"><img class="'.(($guest[2]==1)?"comingyes":"comingmaybe").'"src="'.$guest[1].'" title="@'.(($guest[2]=="yes")?$guest[0]." hat zugesagt":$guest[0]." ist sich noch nicht sicher").'"></a></div><small><a target="_blank" href="https://twitter.com/'.$guest[0].'">@'.$guest[0].'</a></small><br>';
    echo '</div>';
}
$stmtCTG->close();
echo '<div class="clearfix"></div><div class="col-sm-12 text-center"><h3><a href="/'.$ctname.'/teilnehmer">Alle Teilnehmer anzeigen</a></h3></div>';
?>
                <div class="clearfix"></div>
            </div>
            <div class="box">
                <div class="col-lg-12 text-center">
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

            </div>
            <?php if (($ctpicenabled) && ($eingetragen)) {
                if ($cttoday || $ctdone){
                    $stmt3 = $db->prepare("SELECT ctphotos.picid AS pID, users.tw_name AS pOwner, ctphotos.views AS pViews, ctphotos.uploadtime AS pTime, ctphotos.filename AS pFile FROM ctphotos LEFT JOIN users ON (ctphotos.uploader=users.tw_uid) WHERE ctphotos.ctid = ? LIMIT 3");
                    $stmt3->bind_param('i', $ctid);
                    if(false===$stmt3->execute()){
                        mylog("dberror", "Fetching photos ct.php: ".$stmt3->error);
                        die("Datenbank-Fehler :c");
                    }
                    $stmt3res=$stmt3->get_result();
                    $stmt3data=$stmt3res->fetch_all();
                    $stmt3->close();

                            echo '
                        <div class="box">
                            <div class="col-lg-12 text-center">
                                <div class="col-lg-12"><h2>Bilder vom CT</h2><hr></div>';
                                if (empty($stmt3data)){
                                    echo '<p>Noch keine Fotos vom CT online, <a href="/'.$ctname.'/bilder/neu">lade jetzt welche hoch!</a></p>';
                                }else{
                                    echo '<a href="/'.$ctname.'/bilder" title="Weitere Bilder vom CT">';
                                    foreach ($stmt3data as $picData) {
                                        echo '<div class="col-lg-4"><img src="/'.$ctname.'/t/'.$picData[0].'.jpg"><p><i>'.$picData[4].'</i> von <i>'.$picData[1].'</i><br>Aufrufe: '.$picData[2].'<br>Datum: '.$picData[3].'</p></div>';
                                    }
                                    echo '<div class="clearfix"></div><h2>Mehr Bilder anzeigen...</h2></a>';
                                }
                                echo '
                            </div>
                        </div>
                        ';
                }else{
                    echo '
                        <div class="box">
                            <div class="col-lg-12 text-center">
                                <div class="col-lg-12"><h2>Bilder vom CT</h2><hr></div>';
                    echo '<p>Hier können (ab dem Tag an dem das CT statt findet) Bilder vom CT hochgeladen werden, welche nur für Teilnehmer des CTs einsehbar sind!</p>';
                    echo '  </div></div>';
                }
            }
            ?>
        </div>

    </div>

<div id="anmeldung" class="modal fade" role="dialog">
  <div class="modal-dialog">

<?php if(isloggedin()):?>
    <?php if($ctdone && (!$ctyesterday && !$cttoday) && $ctpicenabled):?>
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title">Zu #<?=$ctname?> anmelden</h4>
          </div>
          <div class="modal-body">
            <p>Die Anmeldung zu diesem CT ist geschlossen, da dieses CT bereits stattgefunden hat.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Schließen</button>
          </div>
        </div>
    <?php else:?>

        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title">Zu #<?=$ctname?> anmelden</h4>
          </div>
          <div class="modal-body text-center">
            <form role="form" action="/<?=$ctname?>/anmelden" method="post">
                <div class="row">
                    <div class="form-group col-lg-12">
                        <label>Name</label><br>
                        <label id="icon" for="ctuser">
                            <span class="glyphicon glyphicon-user"></span>
                        </label>
                        <input class="disabled" name="ctuser" id="ctuser" type="text" value="<?php echo $_SESSION['twitter_id']; ?>" disabled>
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

                    </div>
                </div>
            </form>
          </div>
          <div class="modal-footer">
            <?php if(isset($guestcoming) && ($guestcoming!=0) && ($ctmacher!=$_SESSION["twitter_id"])){ echo '<button type="button" class="btn btn-danger" onclick="deleteRegistration();">Vom CT abmelden</button>';}?>
            <button type="button" class="btn btn-success" onclick="$('#realsubmitter').click();"><?php if(isset($guestcoming)){ echo "CT Anmeldung &auml;ndern";}else{echo "Zum CT anmelden";}?></button>
          </div>
        </div>
    <?php endif;?>
<?php else:?>
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Zu #<?=$ctname?> anmelden</h4>
      </div>
      <div class="modal-body">
        <p>Um dich zu diesem CT einzutragen, musst du dich erst auf deinCT anmelden/registrieren.</p>
      </div>
      <div class="modal-footer">
        <a href="/login?ref=<?php echo urlencode(ltrim($_SERVER['REQUEST_URI'],"/"));?>"><button type="button" class="btn btn-info">Anmelden/Registrieren</button></a>
        <button type="button" class="btn btn-danger" data-dismiss="modal">Abbrechen</button>
      </div>
    </div>

<?php endif;?>

  </div>
</div>

<?php
//quick check to see if the user came from the creation page and show him the share modal!
$ctfrischerstellt=false;
if(isset($_SERVER["HTTP_REFERER"])){
    $lastref=explode('/',$_SERVER["HTTP_REFERER"],4);
    if(count($lastref)==4 && $lastref[3]=="cts/neu"){
        $ctfrischerstellt=true;
    }
}else{
    $ctfrischerstellt=false;
}


if($ctfrischerstellt):?>

<div id="ctneu" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title">CT erfolgreich erstellt!</h4>
          </div>
          <div class="modal-body">
            <p>Dein CT wurde erfolgreich erstellt! Teile es jetzt auf Twitter, damit noch mehr Leute drauf aufmerksam werden:<br></p>
            <p class="text-center"><a class="twitter-share-button" href="https://twitter.com/share" data-size="large" data-url="https://deinct.de/<?=$ctname?>" data-count-url="https://deinct.de/<?=$ctname?>" data-via="CTDeutschland" data-text="Meld dich jetzt zu #<?=$ctname?> an!" data-lang="de"></a></p>
          </div>
          <div class="modal-footer">
              <button type="button" class="btn btn-default" data-dismiss="modal">Schließen</button>
          </div>
        </div>

    </div>
</div>


<?php
endif;
?>


<?php
genFooter();
?>
<script>
    $("#ctteilnehmer").text("<?=$generalcounter?>");
    $("#ctcounter").html("<?=$ctcounter?>");
    <?php /**if ($eingetragen){echo '$("#ctchanbut").show();$("#ctanbut").hide();';}**/?>
    <?php /**if ($ctdone){echo '$("#ctanbut").hide();$("#ctchanbut").hide();';}**/?>
    <?php
        if ($eingetragen){
            if ($guestcoming==0){
                echo '$("#amiregistered").text("Du bist für dieses CT abgemeldet!").show();';
            }else{
                echo '$("#amiregistered").show();';
            }

            if ($ctdone && $ctpicenabled){
                if($cttoday || $ctyesterday){
                    //ct ist heute oder war gestern.
                    echo '$("#ctanbut").addClass("btn-success").removeClass("btn-info").text("Anmeldung ändern");';
                }else{
                    //ct war vorgestern oder länger her.
                    echo '$("#ctanbut").addClass("btn-default").removeClass("btn-info").text("Anmeldung nicht mehr möglich");';

                }
            }else{
                //ct war noch nicht
                if ($guestcoming==0){
                    echo '$("#ctanbut").addClass("btn-success").removeClass("btn-info").text("Erneut anmelden");';
                }else{
                    echo '$("#ctanbut").addClass("btn-success").removeClass("btn-info").text("Anmeldung ändern");';
                }

            }

        }else{
            //nicht zum ct eingetragen
            if ($ctdone){
                if($cttoday || $ctyesterday){
                    //ct ist heute oder war gestern.
                    echo '$("#ctanbut").addClass("btn-success").removeClass("btn-info").text("Schnell noch anmelden!");';
                }else{
                    //ct war vorgestern oder länger her.
                    echo '$("#ctanbut").addClass("btn-default").removeClass("btn-info").text("Anmeldung nicht mehr möglich");';
                }
            }else{
                //ct war noch nicht
            }
        }

        if($ctfrischerstellt){
            echo '$("#ctneu").modal("show");';
        }
        ?>

    function deleteRegistration(){
        $('<form action="/<?=$ctname?>/anmelden" method="POST">' +
            '<input type="hidden" name="privacy" value="private">' +
            '<input type="hidden" name="coming" value="no">' +
            '<input type="hidden" name="formsend" value="yes">'+
            '<input type="hidden" name="ctuser" value="<?=((isset($_SESSION["twitter_id"]))?$_SESSION["twitter_id"]:"none")?>">' +
            '</form>').submit();
    }

    twname_regexp = /@([a-zA-Z0-9_]+)/g;
    function linkTwNames(text) {
        return text.replace(
            twname_regexp,
            '<a href="https://twitter.com/$1" target="_blank">@$1</a>'
        );
    }

    $(document).ready(function(){
        $("#descriptionofct").html(linkTwNames($("#descriptionofct").html()));
        //$("#teilnehmer").html(linkTwNames($("#teilnehmer").html()));
        $('img').each(function(){
            $(this).on('error', function() { console.log("image from twitter does not exist anymore. replace with blank image"); $(this).attr("src", "/img/nopic.jpg") })
        });
    });
</script>
<script type="text/javascript" src="//rendro.github.io/countdown/javascripts/jquery.countdown.js"></script>
<script>window.twttr = (function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0],
    t = window.twttr || {};
  if (d.getElementById(id)) return t;
  js = d.createElement(s);
  js.id = id;
  js.src = "https://platform.twitter.com/widgets.js";
  fjs.parentNode.insertBefore(js, fjs);

  t._e = [];
  t.ready = function(f) {
    t._e.push(f);
  };

  return t;
}(document, "script", "twitter-wjs"));</script>
<?php
genAnalytics();
if ($dothecountdown){
    echo '<script type="text/javascript">
                $(function() {
                    $(".countdown").countdown({
                        date: "'.$cttimecount.'",
                        refresh: 1000,
                        render: function(data) {
                            $(this.el).html(this.leadingZeros(data.days, 1) + " <span>Tage, </span>" + this.leadingZeros(data.hours, 2) + " <span>Stunden, </span>" + this.leadingZeros(data.min, 2) + " <span>Minuten und </span>" + this.leadingZeros(data.sec, 2) + " <span>Sekunden</span> bis zum CT!");
                      }
                    });
                });
    </script>';
}
$endzeit=microtime(true)-$startzeit;
$endzeit=round($endzeit, 3, PHP_ROUND_HALF_UP);
echo '<!-- Ladezeit: '.$endzeit.' Sekunden -->';
genFooter(1);
$db->close();
?>
