<?php
include_once('config.inc.php');
htmlOpen();
?>


    <div class="container">

        <div class="row">
            <div class="box">
                <div class="col-lg-12 text-center">
                    <h2 class="brand-before">
                        <small>Willkommen bei</small>
                    </h2>
                    <h1 class="brand-name">deinCT</h1>
                    <hr class="tagline-divider">
                    <h2>
                        <small>Vom
                            <strong><a href="http://twitter.com/derN3rd" target="_blank">N3rd</a></strong>
                        </small>
                    </h2>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="box">
                <div class="col-lg-12">
                    <hr>
                    <h2 class="intro-text text-center">Herzlich Willkommen bei <strong>deinCT</strong>
                    </h2>
                    <hr>
                    <img class="img-responsive img-border img-left" src="img/fernsehturm.jpg" alt="">
                    <hr class="visible-xs">
                    <p class="text-justify">Du willst ein CT planen? Aber dir fehlt der Überblick oder ein Zeitpunkt an dem die meisten Zeit haben? Dann bist du hier richtig! Diese Webseite soll dir dazu helfen dein CT bestmöglich zu organisieren, sodass jeder sehen kann wann und wo ein CT statt findet und wer alles daran teilnimmt! So behältst du alles im Blick und deinem CT steht nichts mehr im Weg!</p>
                    <p class="clearfix"></p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="box">
                <div class="col-lg-12">
                <?php if($showfeedbackbutton):?><div class="ribbon"><span><a href="/feedback">Feedback</a></span></div><?php endif;?>
                    <hr>
                    <h2 class="intro-text text-center">Über diese Seite</h2>
                    <hr>
                    <p class="text-justify">Diese Website ist ein Hobby-Projekt. Alleine gestaltet und verwaltet vom <a href="http://twitter.com/derN3rd" target="_blank">N3rd</a>. Sollte etwas mal nicht funktionieren bin ich gerne über Twitter zu erreichen, aber es ist und bleibt ein Hobby-Projekt, welches ich in meiner Freizeit betreibe.</p>
                    <div id="carousel-ct" class="carousel slide">
                        <ol class="carousel-indicators hidden-xs">
                            <li data-target="#carousel-ct" data-slide-to="0" class="active"></li>
                            <li data-target="#carousel-ct" data-slide-to="1"></li>
                            <li data-target="#carousel-ct" data-slide-to="2"></li>
                            <li data-target="#carousel-ct" data-slide-to="3"></li>
                            <li data-target="#carousel-ct" data-slide-to="4"></li>
                            <li data-target="#carousel-ct" data-slide-to="5"></li>
                            <li data-target="#carousel-ct" data-slide-to="6"></li>
                            <li data-target="#carousel-ct" data-slide-to="7"></li>
                            <li data-target="#carousel-ct" data-slide-to="8"></li>
                        </ol>

                        <div class="carousel-inner">
                            <div class="item active">
                                <img class="img-responsive img-full" src="img/ct-1.jpg" alt="">
                            </div>
                            <div class="item">
                                <img class="img-responsive img-full" src="img/ct-2.jpg" alt="">
                            </div>
                            <div class="item">
                                <img class="img-responsive img-full" src="img/ct-3.jpg" alt="">
                            </div>
                            <div class="item">
                                <img class="img-responsive img-full" src="img/ct-4.jpg" alt="">
                            </div>
                            <div class="item">
                                <img class="img-responsive img-full" src="img/ct-5.jpg" alt="">
                            </div>
                            <div class="item">
                                <img class="img-responsive img-full" src="img/ct-6.jpg" alt="">
                            </div>
                            <div class="item">
                                <img class="img-responsive img-full" src="img/ct-7.jpg" alt="">
                            </div>
                            <div class="item">
                                <img class="img-responsive img-full" src="img/ct-8.jpg" alt="">
                            </div>
                            <div class="item">
                                <img class="img-responsive img-full" src="img/ct-9.jpg" alt="">
                            </div>
                        </div>
                        <a class="left carousel-control" href="#carousel-ct" data-slide="prev">
                            <span class="icon-prev"></span>
                        </a>
                        <a class="right carousel-control" href="#carousel-ct" data-slide="next">
                            <span class="icon-next"></span>
                        </a>
                    </div>
                </div>
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
                <div class="clearfix"></div>
                </div>
            </div>
        </div>

    </div>



<?php
genFooter();
genAnalytics();
?>
<script>
$('.carousel').carousel({
    interval: 8000
})
</script>
<?php
genFooter(1);
?>