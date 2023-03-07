<?php


function wkt2geojson($wkt){
	$coordsstart = strpos($wkt,"(");
	$type = trim(substr($wkt,0,$coordsstart));
	$coordstring = substr($wkt, $coordsstart);

	switch ($type) {
	    case "LINESTRING":
	    	$geom = array("type"=>"LineString","coordinates"=>array());
			$coordstring = str_replace(array("(",")"), "", $coordstring);
	    	$pairs = explode(",", $coordstring);
	    	foreach ($pairs as $k => $v) {
	    		$coords = explode(" ", $v);
	    		$geom['coordinates'][] = array((double)$coords[0],(double)$coords[1]);
	    	}
	    	return $geom;
	    	break;
	    case "POLYGON":
	    	$geom = array("type"=>"Polygon","coordinates"=>array());
			$coordstring = str_replace(array("(",")"), "", $coordstring);
	    	$pairs = explode(",", $coordstring);
	    	foreach ($pairs as $k => $v) {
	    		$coords = explode(" ", $v);
	    		$geom['coordinates'][0][] = array((double)$coords[0],(double)$coords[1]);
	    	}
	    	return $geom;
	    	break;
	    case "MULTILINESTRING":
	    	$geom = array("type"=>"MultiLineString","coordinates"=>array());
	    	preg_match_all("/\([0-9. ,]+\)/",$coordstring,$matches);
	    	//print_r($matches);
	    	foreach ($matches[0] as $linestring) {
	    		$linestring = str_replace(array("(",")"), "", $linestring);
		    	$pairs = explode(",", $linestring);
		    	$line = array();
		    	foreach ($pairs as $k => $v) {
		    		$coords = explode(" ", $v);
		    		$line[] = array((double)$coords[0],(double)$coords[1]);
		    	}
		    	$geom['coordinates'][] = $line;
	    	}
	    	return $geom;
	    	break;
	    case "POINT":
			$coordstring = str_replace(array("(",")"), "", $coordstring);
	    	$coords = explode(" ", $coordstring);
	    	print_r($coords);
	    	$geom = array("type"=>"Point","coordinates"=>array((double)$coords[0],(double)$coords[1]));
	    	return $geom;
	        break;
	}
}

$sparqlquery = '
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
PREFIX dc: <http://purl.org/dc/elements/1.1/>
PREFIX dct: <http://purl.org/dc/terms/>
PREFIX sem: <http://semanticweb.cs.vu.nl/2009/11/sem/>
PREFIX foaf: <http://xmlns.com/foaf/0.1/>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
PREFIX geo: <http://www.opengis.net/ont/geosparql#>
SELECT ?cho ?year ?img ?title ?streetname ?wkt WHERE {
	?cho dct:spatial <' . $_GET['uri'] . '> .
	?cho dc:title ?title .
	?cho sem:hasBeginTimeStamp ?start .
	?cho foaf:depiction ?img .
	<' . $_GET['uri'] . '> geo:hasGeometry/geo:asWKT ?wkt .
	<' . $_GET['uri'] . '> skos:prefLabel ?streetname .
	BIND (year(xsd:dateTime(?start)) AS ?year) .
}
ORDER BY ASC(?start)
LIMIT 20
';

echo $sparqlquery . "\n\n";



$url = "https://api.druid.datalegend.net/datasets/adamnet/all/services/endpoint/sparql?query=" . urlencode($sparqlquery) . "";

$querylink = "https://druid.datalegend.net/AdamNet/all/sparql/endpoint#query=" . urlencode($sparqlquery) . "&endpoint=https%3A%2F%2Fdruid.datalegend.net%2F_api%2Fdatasets%2FAdamNet%2Fall%2Fservices%2Fendpoint%2Fsparql&requestMethod=POST&outputFormat=table";


// Druid does not like url parameters, send accept header instead
$opts = [
    "http" => [
        "method" => "GET",
        "header" => "Accept: application/sparql-results+json\r\n"
    ]
];

$context = stream_context_create($opts);

// Open the file using the HTTP headers set above
$json = file_get_contents($url, false, $context);

echo $url;

$data = json_decode($json,true);

$firstrow = $data['results']['bindings'][0];

$geojson = json_encode(wkt2geojson($firstrow['wkt']['value']));


?>
<html>
<head>
	
	<title>Amsterdams Pionieren</title>

	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	
	<link href="https://fonts.googleapis.com/css?family=Nunito:300,700" rel="stylesheet">

	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

	<link rel="stylesheet" href="https://unpkg.com/leaflet@1.1.0/dist/leaflet.css" integrity="sha512-wcw6ts8Anuw10Mzh9Ytw4pylW8+NAD4ch3lqm9lzAsTxg0GFeJgoAtxuCLREZSC5lUXdVyo/7yfsqFjQ4S+aKw==" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.1.0/dist/leaflet.js" integrity="sha512-mNqn2Wg7tSToJhvHcqfzLMU6J4mkOImSPTxVZAdo+lcPlk+GhZmYgACEe0x35K7YzW1zJ7XyJV/TT1MrdXvMcA==" crossorigin=""></script>

    <style>
		html, body{
			height: 100%;
			margin:0;
			padding: 10px;
			text-align: center;
			font-family: 'Nunito', sans-serif;
		}
		#pics img{
			width: 100%;
			border-radius: 15px;
			margin-top: 20px;
			margin-bottom: 10px;
			border:2px solid #000;
		}
		h1{
			font-size: 48px;
			margin-top: 30px;
		}
		a{
			color: #000;
			text-decoration: none;
		}
		a:hover{
			color: #000;
			text-decoration: none;
		}
		#map{
			height: 300px;
			width: 100%;
		}
	</style>

	
</head>
<body>

<div class="container">
	<div class="row">
		<div class="col-md-3">
		</div>

		<div class="col-md-6">

			<h1><?= $firstrow['streetname']['value'] ?></h1>
			<div class="map" id="map"></div>


			
			<?php

			echo '<div id="pics">';
			foreach ($data['results']['bindings'] as $row) {
				echo '<div class="pic">';
				echo '<a title="' . $row['title']['value'] . '" target="_blank" href="' . $row['cho']['value'] . '">';
				echo '<img src="' . $row['img']['value'] . '">';
				echo '</a>';
				if(isset($row->year)){
					$year = substr($row->year,0,4);
				}else{
					$year = "????";
				}
				echo '<h2>' . $row['year']['value'] . '</h2>';
				echo '</div>';


			}
			if(count($data['results']['bindings'])>1){
				echo '<a href="' . $querylink . '">SPARQL it yourself &gt;</a>';
			}
			echo '</div>';

			?>


		<div class="col-md-3">
		</div>
	</div>
</div>
<script>

	var southWest = L.latLng(52.335, 4.835), northEast = L.latLng(52.398, 4.964), bounds = L.latLngBounds(southWest, northEast);
	var map = L.map('map').fitBounds(bounds);

	L.tileLayer('https://cartodb-basemaps-{s}.global.ssl.fastly.net/light_all/{z}/{x}/{y}.png', {
	    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>, &copy;<a href="https://carto.com/attribution">CARTO</a>',
	    maxZoom: 17
	}).addTo(map);


	var geojsonFeature = {
	    "type": "Feature",
	    "properties": {
	        "name": "<?= $firstrow['streetname']['value'] ?>"
	    },
	    "geometry": <?= $geojson ?>    
	};

	var myStyle = {
	color: "#FC0011",
	weight: 5,
	opacity: 1,
	fillOpacity: 0.5
	};

	var placesLayer = L.geoJson().addTo(map);
	var geom = placesLayer.addData(geojsonFeature);
	L.geoJson(geojsonFeature, {
	    style: myStyle
	}).addTo(map);
	map.fitBounds(placesLayer.getBounds());




</script>

</body>
</html>

