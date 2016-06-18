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

//TODO: Here should be checked if the user is logged in for this event. otherwise he should be redirected to the event page.
$stmt2 = $db->prepare("SELECT ctguests.coming FROM ctguests WHERE ctguests.ctid = ? AND ctguests.tw_id = ? LIMIT 1");
$stmt2->bind_param('is', $thisctid, $_SESSION["twitter_uid"]);
if(false===$stmt2->execute()){
    mylog("dberror", "Fetching if user registered for event picture.php: ".$stmt2->error);
    die("Datenbank-Fehler :c");
}
$stmt2->bind_result($usercoming);
$stmt2->fetch();
$stmt2->close();
if($usercoming === null ){
    header("Location: /".$thisctname);
}
$singlepic=false;
if(isset($_GET["picid"])){
    $stmt3 = $db->prepare("SELECT ctphotos.picid AS pID, users.tw_name AS pOwner, ctphotos.views AS pViews, ctphotos.uploadtime AS pTime, ctphotos.filename AS pFile, ctphotos.uploader AS pOwnerID FROM ctphotos LEFT JOIN users ON (ctphotos.uploader=users.tw_uid) WHERE ctphotos.ctid = ? AND ctphotos.picid=? LIMIT 1");
    $stmt3->bind_param('is', $thisctid, $sqlwhensingle);
    $sqlwhensingle = $_GET["picid"];
    $singlepic=true;
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
if($singlepic && empty($stmt3data)){
    header("Location: /".$thisctname."/bilder");
    //TODO: don't just redirect. display message to user
}else{
    if(!isset($_GET["fullsize"])){
        $stmtY = $db->prepare("UPDATE ctphotos SET views = views+1 WHERE ctid=? AND picid=?");
        $stmtY->bind_param('is', $thisctid, $_GET["picid"]);
        if(false===$stmtY->execute()){
            mylog("dberror", "Updating view count in picture.php: ".$stmtY->error);
            die("Datenbank-Fehler :c");
        }
        $stmtY->close();
    }
}
//var_dump($stmt3data);
//exit();

if (isset($_GET["fullsize"]) && $_GET["fullsize"]=="true"){
    header("Content-type: image/jpeg");
    readfile($config_root."data/photos/".$thisctid."/".$stmt3data[0][0]."_".$stmt3data[0][4]);
}
if (isset($_GET["download"]) && $_GET["download"]=="true"){
    header('Content-type: image/jpeg');
    header('Content-Disposition: attachment; filename="'.$stmt3data[0][4].'"');
    readfile($config_root."data/photos/".$thisctid."/".$stmt3data[0][0]."_".$stmt3data[0][4]);
}

if (isset($_GET["download"]) && $_GET["download"]=="zip"){
    //TODO: after each upload, generate new zip in background task.
    // so the user doesnt need to regenerate the zip each request
    if(file_exists("data/temp/".$thisctid."_photos.zip")){

    }
    $files = array('readme.txt', 'test.html', 'image.gif');
    $zipname = 'data/temp/'.$thisctid.'_photos.zip';
    $zip = new ZipArchive;
    $zip->open($zipname, ZipArchive::CREATE);
    foreach ($files as $file) {
      $zip->addFile($file);
    }
    $zip->close();
    header('Content-Type: application/zip');
    header('Content-disposition: attachment; filename='.$zipname);
    header('Content-Length: ' . filesize($zipname));
    readfile($zipname);
}



htmlOpen('Bilder | #'.$thisctname);

if (isset($_GET["delete"]) && isset($_GET["picid"]) && $_GET["delete"]=="true"){
    //delete
    if ($stmt3data[0][1] == $_SESSION["twitter_id"]){
        $stmtX = $db->prepare("DELETE FROM ctphotos WHERE ctid=? AND picid=? AND uploader=?");
        $actualpicid=$_GET["picid"];
        $stmtX->bind_param("iss", $thisctid, $actualpicid, $_SESSION["twitter_uid"]);
        if(!$stmtX->execute()){
            echo '<script>alert("Fehler beim löschen vom Bild.");</script>';
        }else{
            //unlink("data/photos/".$thisctid."/".$stmt3data[0][0]."_".$stmt3data[0][4]);
            rename($config_root."data/photos/".$thisctid."/".$stmt3data[0][0]."_".$stmt3data[0][4], $config_root."data/photos/".$thisctid."/rm_at_".time()."_".$stmt3data[0][4]);
            unlink("data/t/".$stmt3data[0][0].".jpg");
            header("Location: /".$thisctname."/bilder");
        }
        $stmtX->close();
    }else{
        header("Location: /".$thisctname."/bilder");
    }
}

?>

    <div class="container">

        <div class="row">
            <div class="box">
                <div id="nopics" class="col-lg-12" style="display:none;">
                    <div class="alert alert-info alert-dismissible" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
  <strong>Hm...</strong> Wie es aussieht wurden noch keine Bilder von diesem CT hochgeladen. <a href="/<?=$thisctname?>/bilder/neu">Lade jetzt Bilder hoch!</a>
                    </div>
                </div>
                <div class="col-sm-12 text-center">
                    <?php
                    if($stmt3data){
                        if (isset($_GET["picid"])){
                            echo '<hr><h4 class="text-center"><a href="/'.$thisctname.'/bilder">Zurück zu den Bildern</a></h4><hr>';
                            $rmlink="";
                            if($_SESSION["twitter_uid"] == $stmt3data[0][5]){
                                $rmlink='<br><a id="rmlink" href="/'.$thisctname.'/bild/'.$stmt3data[0][0].'/entfernen">Bild löschen</a>';
                            }
                            echo '<div class="col-lg-12"><img src="/'.$thisctname.'/bild/'.$stmt3data[0][0].'.jpg" style="width:1024px;"><p><i>'.$stmt3data[0][4].'</i> von <i>'.$stmt3data[0][1].'</i><br>Aufrufe: '.$stmt3data[0][2].'<br>Datum: '.$stmt3data[0][3].'<br><a href="/'.$thisctname.'/bild/d/'.$stmt3data[0][0].'">Download</a>'.$rmlink.'<br><small><a href="/'.$thisctname.'/bild/'.$stmt3data[0][0].'/melden">Bild melden</a></small></p></div>';
                        }else{
                            echo '<hr><h4 class="text-center"><a href="/'.$thisctname.'">Zurück zum CT</a> | <a href="/'.$thisctname.'/bilder/neu">Bilder hochladen</a></h4><hr>';
                            foreach ($stmt3data as $picData) {
                                echo '<div class="col-lg-4"><a href="/'.$thisctname.'/bild/'.$picData[0].'"><img src="/'.$thisctname.'/t/'.$picData[0].'.jpg"></a><p><i>'.$picData[4].'</i> von <i>'.$picData[1].'</i><br>Aufrufe: '.$picData[2].'<br>Datum: '.$picData[3].'</p></div>';
                            }
                        }
                    }else{
                        echo '<hr><h4 class="text-center"><a href="/'.$thisctname.'">Zurück zum CT</a></h4><hr>';
                    }
                    ?>
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

if(empty($stmt3data)){
    echo '<script>$("#nopics").show();</script>';
}
?>

<script type="text/javascript">
    $("#rmlink").click(function() {
        if(confirm("Willst du das Bild wirklich löschen?")){
           return true; 
        }else{
            return false;
        }
    });
</script>

<?php
genFooter(1);
$db->close();
?>