<?php
include_once('config.inc.php');
if(!isset($_GET["id"])){//if user opens ct.php directly
    header("Location: /index");
    exit();
}
@$db = new mysqli($MYSQL_HOST, $MYSQL_USER, $MYSQL_PW, $MYSQL_DB);
if (mysqli_connect_errno()) {
    mylog("dberror", "Connection error ct.php(1): ".mysqli_connect_error());
    die("Datenbank Fehler :c");
}
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
$cttoday=false;
$ctdone=false;
$ctyesterday=false;
if ($numRows < 1){
    header("Location: /index");
    exit();
}else{
    if ($cttime < time()){
        $ctdone=true;
    }else{
        $ctdone=false;
    }
    if(date("d.m.Y", time()) == date("d.m.Y", $cttime)){
            $cttoday=true;
    }else{
        $cttoday=false;
    }
    if(date("d.m.Y", time()-86400) == date("d.m.Y", $cttime)){
            $ctyesterday=true;
    }else{
        $ctyesterday=false;
    }
    $cttimecount=date("F d, Y H:i:s", $cttime);
    $cttime=date("d.m.Y H:i", $cttime)." Uhr";
    $betterplace=urlencode($ctplace);
}

htmlOpen("Teilnehmer | "."#".$ctname);
?>

    <div class="container">

        <div class="row">

            <div class="box" id="teilnehmer">
                <div class="col-lg-12">
                    <hr><h4 class="text-center"><a href="/<?=$ctname?>">Zurück zum CT</a></h4><hr>
                </div>
                <div class="col-lg-12">
                    <h2 class="text-center">#<?=$ctname?> -  Teilnehmer</h2>
                    <p class="text-center" id="ctcounter">Ingesamt x (n davon privat)</p>
                    <hr>
                </div>
<?php
$generalcounter=0;
$hiddencounter=0;
$yescounter=0;
$maybecounter=0;
$ctcounter="";
$sql_befehl = "SELECT u.tw_uid,u.tw_name,u.tw_pic,u.tw_bio,ctg.coming,ctg.privacy FROM ctguests AS ctg LEFT JOIN users AS u ON ctg.tw_id = u.tw_uid WHERE ctg.ctid = ".$ctid." AND ctg.coming!=0 ORDER BY ctg.id DESC";
$eingetragen=false;
if ($resultat = $db->query($sql_befehl)) {
    for ($res = array(); $tmp = $resultat->fetch_array(MYSQLI_ASSOC);){
        if($tmp["coming"]!=0){
            $generalcounter++;
        }

        if(isloggedin()){
        	if ($tmp["tw_name"]==$_SESSION["twitter_id"]){
        	    $eingetragen=true;
        	}
        }
        if($tmp["coming"]==1){
            $yescounter++;
        }elseif($tmp["coming"]==2){
            $maybecounter++;
        }else{
            //$nocounter++;
        }
        if ($tmp["privacy"]==1){

            echo '<div class="col-sm-12"><table style="width:100%;"><tr><td class="ctuserimg">';
            echo '<a target="_blank" href="https://twitter.com/'.$tmp["tw_name"].'"><img class="';
            if ($tmp["coming"]==1){
                echo 'comingyes';
            }elseif($tmp["coming"]==2){
                echo 'comingmaybe';
            }else{
                echo 'comingno';
            }
            echo '" src="'.$tmp["tw_pic"].'"></a></td>';
            echo '<td class="ctusertext"><h3>@'.$tmp["tw_name"];
            if($tmp["tw_uid"]==$ctcreator){echo ' (Veranstalter)';}
            echo '</h3><p><i>'.$tmp["tw_bio"].'</i></p><p>Zusage: ';
            if($tmp["coming"]==1){echo '<i class="fa fa-circle comingyes"></i> Ja';}elseif($tmp["coming"]==0){echo '<i class="fa fa-circle comingno"></i> Nein';}else{echo '<i class="fa fa-circle comingmaybe"></i> Vielleicht';}
            echo '</p></td></tr></table><hr></div>';
        }else{
            if((isset($_SESSION["twitter_uid"]) && $_SESSION["twitter_uid"]==$ctcreator) || (isset($_SESSION["twitter_uid"]) && $tmp["tw_uid"]==$_SESSION["twitter_uid"]) || isAdmin()){
                echo '<div class="col-sm-12" style="opacity:0.3;"><table style="width:100%;"><tr><td class="ctuserimg">';
                echo '<a target="_blank" href="https://twitter.com/'.$tmp["tw_name"].'"><img class="';
                if ($tmp["coming"]==1){
                    echo 'comingyes';
                }elseif($tmp["coming"]==2){
                    echo 'comingmaybe';
                }else{
                    echo 'comingno';
                }
                echo '" src="'.$tmp["tw_pic"].'"></a></td>';
                echo '<td class="ctusertext"><h3>@'.$tmp["tw_name"];
                if($tmp["tw_uid"]==$ctcreator){echo ' (Veranstalter)';}
                echo '</h3><p><i>'.$tmp["tw_bio"].'</i></p><p>Zusage: ';
                if($tmp["coming"]==1){echo '<i class="fa fa-circle comingyes"></i> Ja';}elseif($tmp["coming"]==0){echo '<i class="fa fa-circle comingno"></i> Nein';}else{echo '<i class="fa fa-circle comingmaybe"></i> Vielleicht';}
                echo '</p></td></tr></table><hr></div>';
            }
            $hiddencounter++;
        }
    }
    if ($generalcounter >= 1){
        $ctcounter="Es haben sich insgesamt <b>$generalcounter</b> Personen angemeldet. (<b>$hiddencounter</b> davon als privat.)<br><i><b>$yescounter</b> Personen haben fest zugesagt, während sich <b>$maybecounter</b> noch unsicher sind.</i>";
    }else{
        $ctcounter="Es haben sich bisher keine Personen für dieses CT angemeldet!";
    }
    $resultat->close();
}else{
    echo '<div class="col-sm-12 text-center"><p>Datenbank Fehler :c</p></div>';
    mylog("dberror", "Fetching ct users (ctid: $ctid) ctusers.php");
}
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
        </div>

    </div>


<?php
genFooter();
?>
<script>
    $("#ctteilnehmer").text("<?=$generalcounter?>");
    $("#ctcounter").html("<?=$ctcounter?>");
    <?php if ($eingetragen){echo '$("#ctchanbut").show();$("#ctanbut").hide();';}?>
    <?php if ($ctdone && (!($cttoday || $ctyesterday))){echo '$("#ctanbut").hide();$("#ctchanbut").hide();';}?>

    twname_regexp = /@([a-zA-Z0-9_]+)/g;
    function linkTwNames(text) {
        return text.replace(
            twname_regexp,
            '<a href="https://twitter.com/$1" target="_blank">@$1</a>'
        );
    }

    $(document).ready(function(){
        $("#teilnehmer").html(linkTwNames($("#teilnehmer").html()));
        $('img').each(function(){
            $(this).on('error', function() { console.log("image from twitter does not exist anymore. replace with blank image"); $(this).attr("src", "/img/nopic.jpg") })
        });
    });
</script>
<?php
genAnalytics();
genFooter(1);
$db->close();
?>