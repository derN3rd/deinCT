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
    mylog("dberror", "Fetching ct info picture.php: ".$stmt1->error);
    die("Datenbank-Fehler :c");
}
$stmt1->bind_result($thisctid,$thisctname,$thisctcreator,$thiscttime,$thisctpicenabled);
$stmt1->store_result();
$stmt1rows = $stmt1->num_rows;
$stmt1->fetch();
$stmt1->close();
$cttoday=false;
$ctdone=false;
if($stmt1rows < 1){//CT exisitiert nicht
    header("Location: /index");
}else{
    if ($thiscttime < time()){
        $ctdone=true;
    }else{
        $ctdone=false;
    }
    if(date("d.m.Y", time()) == date("d.m.Y", $thiscttime)){
        $cttoday=true;
    }else{
        $cttoday=false;
    }
}
if(!($cttoday || $ctdone)){
    header("Location: /".$thisctname);
}

if(!$thisctpicenabled){
 header("Location: /".$thisctname);   
}

//TODO: Here should be checked if the user is logged in for this event. otherwise he should be redirected to the event page.
$stmt2 = $db->prepare("SELECT ctguests.coming FROM ctguests WHERE ctguests.ctid = ? AND ctguests.tw_id = ? LIMIT 1");
$stmt2->bind_param('is', $thisctid, $_SESSION["twitter_uid"]);
if(false===$stmt2->execute()){
    mylog("dberror", "Fetching if user registered for event newpicture.php: ".$stmt2->error);
    die("Datenbank-Fehler :c");
}
$stmt2->bind_result($usercoming);
$stmt2->fetch();
$stmt2->close();
if($usercoming === null ){
    header("Location: /".$thisctname);
}

/*if(isset($_GET["picid"])){
    $stmt3 = $db->prepare("SELECT ctphotos.picid AS pID, users.tw_name AS pOwner, ctphotos.views AS pViews, ctphotos.uploadtime AS pTime, ctphotos.filename AS pFile, ctphotos.uploader AS pOwnerID FROM ctphotos LEFT JOIN users ON (ctphotos.uploader=users.tw_uid) WHERE ctphotos.ctid = ? AND ctphotos.picid=? LIMIT 1");
    $stmt3->bind_param('is', $thisctid, $sqlwhensingle);
    $sqlwhensingle = $_GET["picid"];
}else{
    $stmt3 = $db->prepare("SELECT ctphotos.picid AS pID, users.tw_name AS pOwner, ctphotos.views AS pViews, ctphotos.uploadtime AS pTime, ctphotos.filename AS pFile FROM ctphotos LEFT JOIN users ON (ctphotos.uploader=users.tw_uid) WHERE ctphotos.ctid = ?");
    $stmt3->bind_param('i', $thisctid);
}
if(false===$stmt3->execute()){
    mylog("dberror", "Fetching photos picture.php: ".$stmt3->error);
    die("Datenbank-Fehler :c");
}
$stmt3res=$stmt3->get_result();
$stmt3data=$stmt3res->fetch_all();
$stmt3->close();
if(empty($stmt3data)){
    header("Location: /".$thisctname."/bilder");
    //TODO: don't just redirect. display message to user
}else{
    //update the view counter of the picture
}
//var_dump($stmt3data);
//exit();
*/

$valid_formats = array("jpg", "png", "jpeg");
$max_file_size = 1024*1024*10;
$count = 0;
$message=array();
include_once("inc/thumbnail.php");
$stmt3 = $db->prepare("INSERT INTO ctphotos (picid, ctid, uploader, filename) VALUES (?, ?, ?, ?)");
$meakauser=$_SESSION["twitter_uid"];
$stmt3->bind_param("siss", $md5name, $thisctid,$meakauser,$name);


if(isset($_POST) && $_SERVER['REQUEST_METHOD'] == "POST" && ($_FILES['files']['name'][0] != "")){
    // Loop $_FILES to exeicute all files
    foreach ($_FILES['files']['name'] as $f => $name) {     
        if ($_FILES['files']['error'][$f] == 4) {
            $message[] = "Unbekannter Fehler bei $name :c";
            continue; // Skip file if any error found
        }          
        if ($_FILES['files']['error'][$f] == 0) {              
            if ($_FILES['files']['size'][$f] > $max_file_size) {
                $message[] = "$name ist größer als 10MB :c";
                continue; // Skip large files
            }
            elseif( ! in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), $valid_formats) ){
                $message[] = "$name ist ein unerlaubtes Dateiformat.";
                continue; // Skip invalid file formats
            }
            else{ // No error found! Move uploaded files 
                $mypicture = new thumbnail();
                $md5name=md5_file($_FILES["files"]["tmp_name"][$f]);

                if (file_exists("data/photos/".$thisctid."/".$md5name."_".$name)){
                    $message[]= "$name existiert bereits.";
                    continue;
                }
                $stmt4 = $db->prepare("SELECT picid FROM ctphotos WHERE ctid = ? AND picid = ? LIMIT 1");
                $stmt4->bind_param('is', $thisctid, $md5name);
                $stmt4->bind_result($fileexist);
                $stmt4->fetch();
                $stmt4->close();

                if($fileexist === null){
                    if(!$stmt3->execute()){
                        $message[] = "$name konnte nicht hinzugefügt werden.".$stmt3->error;
                        //TODO: remove file and thumbnail here
                        continue;
                    }
                }else{
                    $message[]="$name existiert bereits in der Datenbank!";
                    continue;
                }


                if(!$mypicture->create($_FILES["files"]["tmp_name"][$f])){
                    $message[]="$name ist kein Bild!";
                }
                $mypicture->setQuality(90);
                if($mypicture->orgX > 3000 || $mypicture->orgY > 3000) $mypicture->maxSize("3000");
                if(!$mypicture->save("data/photos/".$thisctid."/".$md5name."_".$name, false)){
                    $message[] = "$name existiert bereits.";
                    $stmt4 = $db->prepare("DELETE FROM ctphotos WHERE ctid = ? AND picid = ? LIMIT 1");
                    $stmt4->bind_param('is', $thisctid, $md5name);
                    $stmt4->execute();
                    $stmt4->close();
                    continue;
                }
                $mypicture->cube(250);
                
                if(!$mypicture->save("data/t/".$md5name.".jpg")){
                    $message[] = "$name (Thumbnail) existiert bereits.";
                }
                $count++; // Number of successfully uploaded file



                
            }
        }
    }
    mylog("user","Uploaded $count Pictures to CT");
    $stmt3->close();
    //var_dump($message);
    //exit();
}



htmlOpen('Neues Bild hochladen | '.$thisctname);
?>
<style>
.spinner {
  -webkit-animation: rotator 1.4s linear infinite;
          animation: rotator 1.4s linear infinite;
}

@-webkit-keyframes rotator {
  0% {
    -webkit-transform: rotate(0deg);
            transform: rotate(0deg);
  }
  100% {
    -webkit-transform: rotate(270deg);
            transform: rotate(270deg);
  }
}

@keyframes rotator {
  0% {
    -webkit-transform: rotate(0deg);
            transform: rotate(0deg);
  }
  100% {
    -webkit-transform: rotate(270deg);
            transform: rotate(270deg);
  }
}
.path {
  stroke-dasharray: 187;
  stroke-dashoffset: 0;
  -webkit-transform-origin: center;
          transform-origin: center;
  -webkit-animation: dash 1.4s ease-in-out infinite, colors 5.6s ease-in-out infinite;
          animation: dash 1.4s ease-in-out infinite, colors 5.6s ease-in-out infinite;
}

@-webkit-keyframes colors {
  0% {
    stroke: #4285F4;
  }
  25% {
    stroke: #DE3E35;
  }
  50% {
    stroke: #F7C223;
  }
  75% {
    stroke: #1B9A59;
  }
  100% {
    stroke: #4285F4;
  }
}

@keyframes colors {
  0% {
    stroke: #4285F4;
  }
  25% {
    stroke: #DE3E35;
  }
  50% {
    stroke: #F7C223;
  }
  75% {
    stroke: #1B9A59;
  }
  100% {
    stroke: #4285F4;
  }
}
@-webkit-keyframes dash {
  0% {
    stroke-dashoffset: 187;
  }
  50% {
    stroke-dashoffset: 46.75;
    -webkit-transform: rotate(135deg);
            transform: rotate(135deg);
  }
  100% {
    stroke-dashoffset: 187;
    -webkit-transform: rotate(450deg);
            transform: rotate(450deg);
  }
}
@keyframes dash {
  0% {
    stroke-dashoffset: 187;
  }
  50% {
    stroke-dashoffset: 46.75;
    -webkit-transform: rotate(135deg);
            transform: rotate(135deg);
  }
  100% {
    stroke-dashoffset: 187;
    -webkit-transform: rotate(450deg);
            transform: rotate(450deg);
  }
}
</style>

    <div class="container">

        <div class="row">
            <div class="box">
                <div id="errors" class="col-lg-12" style="display:none;">
                    <div class="alert alert-danger alert-dismissible" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
  <strong>Oh...</strong>
  <?php
    foreach ($message as $value) {
        echo "$value\n";
    }
  ?>
                    </div>
                </div>
                <div id="allfine" class="col-lg-12" style="display:none;">
                    <div class="alert alert-success alert-dismissible" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
  <strong>Yeay!</strong> Es sieht so aus, als wenn alle Bilder erfolgreich hochgeladen wurden! <a href="/<?=$thisctname?>/bilder">Zu den Bildern</a>
                    </div>
                </div>
                <div class="col-sm-12 text-center">
                <hr><h4 class="text-center"><a href="/<?=$thisctname?>/bilder">Zurück zu den Bildern</a></h4><hr>
                <div id="spinnerloading" class="text-center" style="display:none;">
                        <svg class="spinner" width="65px" height="65px" viewBox="0 0 66 66" xmlns="http://www.w3.org/2000/svg" style="margin-left:auto;margin-right:auto;">
                            <circle class="path" fill="none" stroke-width="6" stroke-linecap="round" cx="33" cy="33" r="30"></circle>
                        </svg>
                </div>
                    <form id="uploadform" action="/<?=$thisctname?>/bilder/neu" method="POST" role="form" enctype="multipart/form-data">
                        <div class="form-group" id="allfiles">
                            <label for="file"></label>
                            <input type="file" id="file" name="files[]" multiple="multiple" accept="image/*" style="margin-left:auto;margin-right:auto;">
                        </div>
                        <div class="form-goup" id="allchecks">
                            <input name="checks" id="checks" type="checkbox" required>
                            <label for="checks" class="checkbox">Ich bestätige, dass alle Personen auf meinen Bildern mit dem Upload hier einverstanden sind.</label>
                        </div>
                        <button class="btn btn-info" type="submit" id="upload">Hochladen</button>

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
    <!-- /.container -->

<?php
genFooter();
genAnalytics();

$error=0;
if(!empty($message)){
    echo '<script>$("#errors").show();</script>';
}elseif(isset($_POST) && $_SERVER['REQUEST_METHOD'] == "POST" && ($_FILES['files']['name'][0] != "")){
    echo '<script>$("#allfine").show();</script>';
}

?>

<script>
$(document).ready(function(){
    $('#uploadform').on('submit', function(e){
        $('#allfiles').slideUp(200);
        $('#allchecks').slideUp(200);
        $('#spinnerloading').slideDown(200);
    });
});
</script>

<?php
genFooter(1);
$db->close();
?>