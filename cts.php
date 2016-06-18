<?php
include_once('config.inc.php');
@$db = new mysqli($MYSQL_HOST, $MYSQL_USER, $MYSQL_PW, $MYSQL_DB);

htmlOpen('Aktuelle CTs');
?>

    <div class="container">

        <div class="row">
            <div class="box">
                <div class="col-lg-12">
                <?php if($showfeedbackbutton):?><div class="ribbon"><span><a href="/feedback">Feedback</a></span></div><?php endif;?>
                    <hr>
                    <h2 class="text-center">Aktuelle CTs</h2>
                    <hr>
                    <h5 class="text-center"><small><a href="/cts/karte">CTs auf der Karte zeigen</a></small></h5>
                </div>
<?php
if (mysqli_connect_errno()) {
    //printf("Datenbank Fehler\n", mysqli_connect_error());
    echo '<div class="col-lg-12 text-center"><h2>Datenbank-Fehler :c</h2></div>';
    mylog("dberror", "Connection error cts.php: ".mysqli_connect_error());
    exit();
}

$heute=date("Y-m-d");
$heutemorgen=strtotime($heute." 00:00:00");

if ($resultat = $db->query("SELECT name, time, place, public FROM cts WHERE public = 1 AND time > ".$heutemorgen." ORDER BY time ASC LIMIT 10")) {
    for ($res = array(); $tmp = $resultat->fetch_array(MYSQLI_ASSOC);){
        $res[] = $tmp;
        echo '<div class="col-sm-12 ctlist text-center"><div class="col-sm-8">';
        echo '<h3><a href="/'.$tmp["name"].'">#'.$tmp["name"].'</a></h3></div>';
        echo '<div class="col-sm-4"><small><u>Wann?</u> ';
        echo date("d.m.Y H:i",$tmp["time"])." Uhr<br>";
        echo '<u>Wo?</u> ';
        echo $tmp["place"];
        echo '</small></div><hr></div>';
    }
    if (sizeof($res) < 1 || $res == array()){
        echo '<div class="col-lg-12 text-center"><h3>Momentan sind keine CTs geplant.</h3></div>';
    }
    $resultat->close();
}else{
    echo '<div class="col-lg-12 text-center"><h2>Datenbank-Fehler :c</h2></div>';
    mylog("dberror", "Fetching list of cts cts.php");
}//mysql

?>
                <div class="clearfix"></div>

            </div>
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
?>

<?php
genFooter(1);
$db->close();
?>