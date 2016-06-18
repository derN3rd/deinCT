<?php
$CONSUMER_KEY='';
$CONSUMER_SECRET='';
$OAUTH_CALLBACK='https://deinct.de/oauth.php';
$MYSQL_USER='user';
$MYSQL_PW='pass';
$MYSQL_HOST='localhost';
$MYSQL_DB='deinCT';
$RECAPTCHA_NEWCT_PUBLIC='';
$RECAPTCHA_NEWCT_PRIVATE='';
$config_root="/var/www/";
$debug=false;
$showfeedbackbutton=true;

session_start();

function isloggedin(){
    if(isset($_SESSION['twitter_uid']) && isset($_SESSION['twitter_id'])){
        $_SESSION['lastchange']=time();
        return true;
    }else{
        return false;
    }
}

function isAdmin(){
    if (isset($_SESSION['isadmin']) && $_SESSION['isadmin'] == true){
        return true;
    }else{
        return false;
    }
}

function needslogin(){
    if (!isloggedin()){
        header("Location: /login?ref=".urlencode($_SERVER['REQUEST_URI']));
        exit();
    }
}

if($debug){
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}else{
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
}

function genTopMenu(){
    if(isset($_SESSION['twitter_uid'])){ //check whether user already logged in with twitter
        if(isAdmin()){
            echo '<li><a href="/n3rdonly/index">Adm</a></li>';
        }
        echo '<li><a href="/profil">Mein Profil</a></li><li><a href="/logout">Logout</a></li>';

    }else{ // Not logged in
        echo '<li><a href="/login?ref='.urlencode(ltrim($_SERVER['REQUEST_URI'],"/")).'">Login</a></li>';
    }
}

function htmlOpen($title=""){
    include('inc/htmlopena.html');
    if ($title == "") echo "<title>deinCT</title>";
    else echo '<title>'.$title.' | deinCT</title>';

    echo '<meta name="twitter:card" content="summary" />';
    echo '<meta name="twitter:site" content="@CTDeutschland" />';
    echo '<meta name="twitter:title" content="deinCT" />';
    echo '<meta name="twitter:description" content="Lass Treffen einfach Treffen sein!" />';
    echo '<meta name="twitter:image" content="http://deinct.de/twitter_card_2016.png" />';
	echo '<meta name="robots" content="index, follow">';
    echo '<meta name="googlebot" content="noarchive" />';
    include('inc/htmlopenb.html');
    genTopMenu();
    include('inc/htmlopenc.html');
}

function htmlOpenCT($title,$twdesc){
    include('inc/htmlopena.html');
    echo '<title>'.$title.' | deinCT</title>';
    echo '<meta name="twitter:card" content="summary" />';
    echo '<meta name="twitter:site" content="@CTDeutschland" />';
    echo '<meta name="twitter:title" content="'.$title.'" />';
    echo '<meta name="twitter:description" content="'.$twdesc.'" />';
    echo '<meta name="twitter:image" content="http://deinct.de/twitter_card_2016.png" />';
	echo '<meta name="robots" content="index, follow">';
    echo '<meta name="googlebot" content="noarchive" />'; //important, so google won't cache userdata displayed here.
    include('inc/htmlopenb.html');
    genTopMenu();
    include('inc/htmlopenc.html');
}

function genFooter($part=0){
    //Part != 0 is the first part before the javascripts
    //Part == 0 is the second part after the javascripts
    if ($part == 0) include('inc/footera.html');
    else include('inc/footerb.html');
}

include_once('inc/log.php');

function nl2br2($string) {
    $string = str_replace(array("\r\n", "\r", "\n"), "<br>", $string);
    return $string;
}

function genAnalytics(){
	echo "<script>
	  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

	  ga('create', 'UA-46234832-3', 'auto');";
	  if(isset($_SESSION["twitter_uid"])){echo "ga('set', '&uid', '".$_SESSION["twitter_uid"]."');\n";}
	  echo "ga('send', 'pageview');</script>";
}

?>