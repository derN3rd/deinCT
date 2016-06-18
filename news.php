<?php
include_once('config.inc.php');
@$db = new mysqli($MYSQL_HOST, $MYSQL_USER, $MYSQL_PW, $MYSQL_DB);

htmlOpen('News');
?>
    <div class="container">

        <div class="row">
            <div class="box">
                <div class="col-lg-12">
                <?php if($showfeedbackbutton):?><div class="ribbon"><span><a href="/feedback">Feedback</a></span></div><?php endif;?>
                    <hr>
                    <h2 class="text-center">News</h2>
                    <hr>
                </div>
<?php

if ($resultat = $db->query("SELECT * FROM news ORDER BY time DESC LIMIT 5")) {
    for ($res = array(); $tmp = $resultat->fetch_array(MYSQLI_ASSOC);){
        $res[]=$tmp;
        echo '<div class="col-lg-12 text-center"><h2>'.$tmp["title"]."<br><small>".date("d.m.Y H:i",$tmp["time"])."</small></h2>\n";
        echo '<p>'.nl2br2($tmp["text"])."</p>\n";
        echo '<hr></div>';
    }
    if (sizeof($res) < 1 || $res == array()){
        echo '<div class="col-lg-12 text-center"><h2>Momentan keine News :c</h2></div>';
    }
    $resultat->close();
}else{
    echo '<div class="col-lg-12 text-center"><h2>Datenbank-Fehler :c</h2></div>';
    mylog("dberror", "Fetching news in news.php");
}//mysql


?>
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
?>

<?php
genFooter(1);
?>