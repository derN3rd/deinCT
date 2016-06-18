<?php
include_once('config.inc.php');
if (isloggedin()){
    header("Location: /index");
    exit();
}
if (isset($_GET["ref"])){
        $_SESSION['ref']=urlencode($_GET["ref"]);
}
if (substr($_SERVER['REQUEST_URI'],0,12) === "/login?login"){
    header("Location: /twlogin");
}
htmlOpen('Login');
?>

    <div class="container">

        <div class="row">
            <div class="box">
                <div class="col-lg-12">
                <?php if($showfeedbackbutton):?><div class="ribbon"><span><a href="/feedback">Feedback</a></span></div><?php endif;?>
                    <hr>
                    <h2 class="text-center">Anmeldung</h2>
                    <hr>
                </div>
                <div class="col-lg-12 text-center">
                    <p>Registrieren ist völlig kostenlos und geht mit nur 2 Klicks! Registriere dich einfach mit Twitter, indem du auf den Anmelden Button dort unten klickst. Twitter und deinCT übernehmen dann den Rest.<br><small>Einloggen geht übrigens genau so einfach ;)</small></p><br>
                    <p><a href="/login?login" class="button" id="butlogin">Anmelden</a></p>
                    <hr>
                    <p class="text-right" style="font-size: 0.8em;">Du fragst dich, was für Daten wir speichern?<br><button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#dataTrans">
  Wirf einen Blick in unsere Datentransparenz
</button></p>
                </div>
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

    
    <!-- Modal -->
<div class="modal fade" id="dataTrans" tabindex="-1" role="dialog" aria-labelledby="dataTransLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">Datentransparenz</h4>
      </div>
      <div class="modal-body">
        <p>Bei deinCT werden von registrierten Benutzern folgende Informationen gesammelt und gespeichert:</p><br>
          <table width="100%" id="datatranstable">
              <tr><th>Daten</th><th>Dauer</th><th>Grund</th></tr>
              <tr><td>Profilbild</td><td>Bis zur Löschung des Accounts.</td><td>Darstellung des Profils bei angemeldeten CTs.</td></tr>
              <tr><td>Username/ID</td><td>Bis zur Löschung des Accounts.</td><td>Eineutige Identifizierung des Benutzers.</td></tr>
              <tr><td>Twitter-Biografie</td><td>Bis zur Löschung des Accounts.</td><td>Darstellung des Profils bei angemeldeten CTs.</td></tr>
              <tr><td>E-Mail (optional)</td><td>Bis zur Löschung des Accounts.</td><td>Mitteilung persönlicher Benachrichtigungen, Überprüfung der Identität.</td></tr>
              <tr><td>IP-Adresse</td><td>31 Tage</td><td>Eindeutige Identifizerung, rechtliche Absicherung bei illegalen Aktivitäten.</td></tr>
          </table>
          <hr>
          <small>Der Bernutzername, das Profilbild und die Biografie werden täglich von Twitter aktualisiert. Alle von deinCT erhobenen Informationen gibt jeder User selbstständig über den Drittanbieter Twitter frei. Dort eingetragene Informationen sind für deinCT nicht beeinflussbar.</small>
          <hr>
          <br>
          <p>Twitter übergibt <b>KEIN</b> Passwort an deinCT, daher besteht keine Möglichkeit personenbezogene Daten auf deinCT zu entwenden.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Schließen</button>
      </div>
    </div>
  </div>
</div>


<?php
genFooter();
?>

<?php
genFooter(1);
?>