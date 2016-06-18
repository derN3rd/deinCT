<?php
include_once('config.inc.php');
require('inc/ReCaptcha.autoload.php');
require('inc/phpmailer/PHPMailerAutoload.php');
needslogin();

function isValid($str) {
    return filter_var($str, FILTER_VALIDATE_EMAIL);
}

@$db = new mysqli($MYSQL_HOST, $MYSQL_USER, $MYSQL_PW, $MYSQL_DB);
if (mysqli_connect_errno()) {
    mylog("dberror", "Connection error newct.php: ".mysqli_connect_error());
    die("Datenbank-Fehler :c");
}
$stmt = $db->prepare("SELECT approved FROM users WHERE tw_uid = ? LIMIT 1");
$stmt->bind_param('s', $_SESSION['twitter_uid']);
if(false===$stmt->execute()){
    mylog("dberror", "Fetching approved status approvemail.php: ".$stmt->error);
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
    if ($isuserapproved == 1){ //user is already approved
        header("Location: /profil");
    }//else -> everthing is fine (user isnt approved)
}

$formerror = array();
$formsuccess = array();
if (isset($_POST["formsend"])){
    if ( ($_POST["usermail"] != "") && (isset($_POST["checkmail"])) ){
        $diderror=false;
        $recaptcha = new \ReCaptcha\ReCaptcha($RECAPTCHA_NEWCT_PRIVATE);
        $resp = $recaptcha->verify($_POST["g-recaptcha-response"], $_SERVER['REMOTE_ADDR']);
        if ($resp->isSuccess()) {
            $diderror=false;
        } else {
            $diderror=true;
            //$errors = $resp->getErrorCodes();
            $formerror[] = "Captcha fehlerhaft!";
            mylog("usererror", "Entered wrong captcha approvemail.php");
        }
        if (!isValid($_POST["usermail"])){
            $diderror=true;
            $formerror[] = "Bitte gib eine gültige E-Mail Adresse ein.";
            mylog("usererror", "Entered invalid Email in approvemail.php");
        }
        
        if(!$diderror){
            $stmt2 = $db->prepare("UPDATE users SET email = ? WHERE tw_uid = ?");
            $stmt2->bind_param('ss', $_POST["usermail"], $_SESSION["twitter_uid"]);
            if(false===$stmt2->execute()){
                mylog("dberror", "Updating usermail approvemail.php: ".$stmt2->error);
                die("Datenbank-Fehler :c");
            }else{
                mylog("user", "Entered mail for approval");
                //TODO: Send mail here---------------------------------------------------------------
                $mailtext = '<html>
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
 <title>Registrierung abschließen</title>
</head>
<body>
 <h1>Hey '.$_SESSION["twitter_id"].',</h1>
 <p>Vielen Dank für deine Anmeldung bei deinCT.<br>Um CTs erstellen zu können, musst du deine E-Mail Adresse bestätigen. Willst du dies nicht, kannst du dich nur zu bestehenden CTs eintragen.</p>
 <p>Deine E-Mail Adresse wird nicht für Werbe-Zwecke genutzt, sondern dient ausschließlich der eindeutigen Identifikation deinerseits und als Möglichkeit dich über Neuerungen bzw Aktionen zu deinem Benutzerkonto zu informieren.</p>
 <p style="border:2px solid grey;padding:5px;"><a href="https://deinct.de/profil/email/'.md5($_SESSION["twitter_uid"].$_POST["usermail"].$_SESSION["twitter_uid"]).'">Klicke hier um deine E-Mail Adresse zu bestätigen.</a></p>
 <p>Viel Spaß wünscht <a href="https://twitter.com/derN3rd">derN3rd</a>!</p>
</body>
</html>
'."\r\n\r\n"; //TODO: this hash is way too weak. Should use something more random like rand() or the current timestamp

                
                $mail = new PHPMailer;
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );
                //$mail->SMTPDebug=4;
                $mail->CharSet="UTF-8";
                $mail->isSMTP();                                      // Set mailer to use SMTP
                $mail->Host = 'localhost';  // Specify main and backup SMTP servers
                $mail->SMTPAuth = true;                               // Enable SMTP authentication
                $mail->Username = 'no-reply@deinct.de';                 // SMTP username
                $mail->Password = 'pass';                           // SMTP password

                $mail->setFrom('no-reply@deinct.de', 'deinCT');
                $mail->addAddress($_POST["usermail"], $_SESSION["twitter_id"]);     // Add a recipient
                $mail->addReplyTo('support@deinct.de', 'Support');

                $mail->isHTML(true);                                  // Set email format to HTML

                $mail->Subject = "Dein Account bei deinCT bestätigen";
                $mail->Body    = $mailtext;
                $mail->AltBody = 'Bitte rufe folgende Webseite auf, um deine E-Mail Adresse zu bestätigen: https://deinct.de/profil/email/'.md5($_SESSION["twitter_uid"].$_POST["usermail"].$_SESSION["twitter_uid"]); //TODO: Den HTML Code als einfache Text Variante bereitstellen

                if(!$mail->send()) {
                    //echo 'Message could not be sent.';
                    //echo 'Mailer Error: ' . $mail->ErrorInfo;
                    $formerror[] = "Die E-Mail konnte nich versendet werden. Bitte versuche es später erneut!";
                    mylog("error", "Could not send Email in approvemail.php");
                } else {
                    $formsuccess[] = "Die E-Mail wurde versandt. Bitte überprüfe demnächst dein Postfach.";
                }

            }
            $stmt2->close();
        }
    }
}

htmlOpen('Account freischalten');
?>

    <div class="container">

        <div class="row">
            <div class="box">
                <div class="col-lg-12">
                    <hr>
                    <h2 class="text-center">E-Mail Adresse bestätigen</h2>
                    <hr>
                </div>
<?php if(!empty($formerror)){
    foreach ($formerror as $einerror){
        echo '<div id="mailerror" class="col-lg-12"><div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Ehm...</strong> '.$einerror.'</div></div>';
    }
}
if(!empty($formsuccess)){
    foreach ($formsuccess as $einsuccess){
        echo '<div id="mailsuccess" class="col-lg-12"><div class="alert alert-success alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Yeay!</strong> '.$einsuccess.'</div></div>';
        echo '<div id="mailhelp" class="col-lg-12"><div class="alert alert-warning alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Achtung!</strong> Es kann sein, dass unsere Emails in deinem Spam Ordner landen. (Du kannst unsere Email-Adresse deinem Adressbuch hinzufügen, um dies zu verhindern.)</div></div>';
    }
}

?>
                <div class="col-lg-12 text-center">
                    <form role="form" action="email" method="post" accept-charset="utf-8">
                        <div class="row">
                            <div class="form-group col-lg-12">
                                <label id="icon" for="usermail">
                                    <span class="glyphicon glyphicon-envelope"></span>
                                </label>
                                <input type="email" name="usermail" placeholder="meine@email.de" <?php if(!empty($formsuccess)){echo "disabled";}?> >
                            </div>
                            <div class="form-goup col-lg-12">
                                <input name="checkmail" id="checkmail" type="checkbox" required <?php if(!empty($formsuccess)){echo "disabled";}?>>
                                <label for="checkmail" class="checkbox">Ich bestätige, dass mir die oben eingegebene E-Mail Adresse gehört.</label>
                            </div>
                            <div class="form-group col-lg-12 text-center">
                                <div class="g-recaptcha" data-sitekey="<?php echo $RECAPTCHA_NEWCT_PUBLIC;?>"></div>
                            </div>
                            <div class="form-group col-lg-12 text-center" <?php if(!empty($formsuccess)){echo 'style="display:none;"';}?>>
                                <a type="submit" id="buttonsub" class="button" onclick="$('#submitterb').trigger('click');">E-Mail anfordern</a>
                            </div>
                        <input type="hidden" name="formsend" value="jepp">
                        <button id="submitterb" type="submit" style="display:none;"></button>
                        </div>
                    </form>
                </div>
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
?>
<script src='https://www.google.com/recaptcha/api.js'></script>
<?php
genFooter(1);
?>