<?php
include_once('config.inc.php');
@$db = new mysqli($MYSQL_HOST, $MYSQL_USER, $MYSQL_PW, $MYSQL_DB);

htmlOpen('Aktuelle CTs (Karte)');
?>

    <div class="container">

        <div class="row">
            <div class="box">
                <div class="col-lg-12">
                <?php if($showfeedbackbutton):?><div class="ribbon"><span><a href="/feedback">Feedback</a></span></div><?php endif;?>
                    <hr>
                    <h2 class="text-center">Aktuelle CTs (Karte)</h2>
                    <hr>
                </div>
                <?php
                if (mysqli_connect_errno()) {
                    //printf("Datenbank Fehler\n", mysqli_connect_error());
                    echo '<div class="col-lg-12 text-center"><h2>Datenbank-Fehler :c</h2></div>';
                    mylog("dberror", "Connection error ctsmap.php: ".mysqli_connect_error());
                    exit();
                }
                ?>
                <div class="col-lg-12">
                    <div id="map" style="height:600px;"></div>
                </div>
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
<script>
    var map;
    function initMap() {
    map = new google.maps.Map(document.getElementById('map'), {
      center: {lat: 51, lng: 11},
      zoom: 6
    });
    }
</script>
<script type="text/javascript"  src="//maps.google.com/maps/api/js?key=AIzaSyB362bAj7q0-3N62OjBB5QEUQ-2Ht8ZvZY&callback=initMap"></script>
<script type="text/javascript" src="//google-maps-utility-library-v3.googlecode.com/svn/trunk/markerclustererplus/src/markerclusterer.js"></script>

<script>
    
        <?php


        $heute=date("Y-m-d");
        $heutemorgen=strtotime($heute." 00:00:00");

        $maparray=array();
        $ctarray=array();
        $stmt = $db->prepare("SELECT name, time, place FROM cts WHERE public = 1 AND time > ? ORDER BY time ASC LIMIT 10");
        $stmt->bind_param('i', $heutemorgen);
        if(false===$stmt->execute()){
            mylog("dberror", "Fetching cts ctsmap.php(1): ".$stmt->error);
            die("Datenbank-Fehler :c");
        }
        $stmt->bind_result($thisctname,$thiscttime,$thisctplace);
        $stmt->store_result();
        $numRows = $stmt->num_rows;
        while($stmt->fetch()){
            $ctdate=date("d.m.Y H:i",$thiscttime);
            $ctsarray[]=array("name"=>$thisctname, "date"=>$ctdate, "place"=>$thisctplace);
        }

        echo "var ctsmore = ".json_encode($ctsarray).";\n";
        $stmt->close();

        ?>
        var geocoder = new google.maps.Geocoder();
        var gmarkers = [];
        var mcOptions = {gridSize: 20, maxZoom: 28};
        var mc = null;
        mc = new MarkerClusterer(map, [], mcOptions);

        var infowindow = new google.maps.InfoWindow({});

        function createMarker(latlng,ct) {

            var marker = new google.maps.Marker({
                draggable: false,
                raiseOnDrag: false,
                position: latlng,
                map: map,
                title: "#"+ct.name,
                ct: ct
            });

            

            google.maps.event.addListener(marker, 'click', function() {
                infowindow.setContent('<div id="content">'+
                    '<div id="siteNotice">'+
                    '</div>'+
                    '<h1 id="firstHeading" class="firstHeading">#'+marker.ct.name+'</h1>'+
                    '<div id="bodyContent">'+
                    '<p><b>Wo?:</b> '+marker.ct.place+'</p>'+
                    '<p><b>Wann?:</b> '+marker.ct.date+'</p>'+
                    '<p><h2><a href="/'+marker.ct.name+'">Zum CT</h2></p>'+
                    '</div>'+
                    '</div>');
                infowindow.open(map,marker);
            });
            mc.addMarker(marker);
            return marker;
        }
        for (i=0; i<ctsmore.length; i++) {
            (function (myCT){
                var newAddress;
                //Key Part Here!!! These should be cached somewhere rather than querying every page refresh like here though.
                geocoder.geocode( { 'address': ctsmore[i].place}, function(results, status) {
                    if (status == google.maps.GeocoderStatus.OK) {
                        newAddress = results[0].geometry.location;
                        var latlng = new google.maps.LatLng(parseFloat(newAddress.lat()),parseFloat(newAddress.lng()));
                        gmarkers.push(createMarker(latlng,myCT));
                    }
                })
            })(ctsmore[i]);
        }

</script>

<?php
genFooter(1);
$db->close();
?>