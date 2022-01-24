<!DOCTYPE html>
<?php

$start = 1919;
$end = 1930;
$title = 1920;
if(isset($_GET['year'])){
	$start = (int)$_GET['year']-1;
	$end = (int)$_GET['year']+10;
	$title = (int)$_GET['year'];
}

$sparqlquery = "PREFIX hg: <http://rdf.histograph.io/>
PREFIX dct: <http://purl.org/dc/terms/>
PREFIX sem: <http://semanticweb.cs.vu.nl/2009/11/sem/>
PREFIX dc: <http://purl.org/dc/elements/1.1/>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX foaf: <http://xmlns.com/foaf/0.1/>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
PREFIX void: <http://rdfs.org/ns/void#>

SELECT DISTINCT ?street ?streetname SAMPLE(?cho) as ?cho SAMPLE(?realchodate) as ?date SAMPLE(?img) as ?img WHERE {
 ?cho foaf:depiction ?img .
 ?cho dc:type ?type .
 FILTER(?type != \"bouwtekening\"^^xsd:string) .
 ?cho dct:spatial ?street .
 ?street a hg:Street .
 ?street rdfs:label ?streetname .
 ?cho sem:hasBeginTimeStamp ?chodate .
 ?street sem:hasEarliestBeginTimeStamp ?streetstart .
 BIND(IF(COALESCE(xsd:datetime(str(?chodate)), '!') != '!',
 	year(xsd:dateTime(str(?chodate))),
 	\"1700\"^^xsd:gYear) AS ?realchodate )
 FILTER (?realchodate > " . $start . ") .
 FILTER (?realchodate < " . $end . ") .
 BIND (?realchodate - year(xsd:dateTime(?streetstart)) AS ?yeardiff) .
 FILTER (?yeardiff < 3) .
 FILTER (?yeardiff > 0) .
}
GROUP BY ?street ?streetname
LIMIT 60
";


//echo $sparqlquery;

$url = "https://api.druid.datalegend.net/datasets/Adamnet/all/services/endpoint/sparql?query=" . urlencode($sparqlquery) . "";
//$url = "https://api.druid.datalegend.net/datasets/AdamNet/all/services/endpoint/sparql"

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

echo $json;
die;

$data = json_decode($json,true);

$all = $data['results']['bindings'];
$col1 = array_slice($all,0,10);
$col2 = array_slice($all,10,10);
$col3 = array_slice($all,20,10);
$col1 = array();
$col2 = array();
$col3 = array();

$i = 0;
foreach ($data['results']['bindings'] as $row) {
	$i++;
	if($i%3==0){
		$col3[] = array(
					"street" => $row['street']['value'],
					"label" => $row['streetname']['value'],
					"img" => $row['img']['value'],
					"link" => $row['cho']['value'],
					"year" => $row['date']['value']
					);
	}elseif($i%2==0){
		$col2[] = array(
					"street" => $row['street']['value'],
					"label" => $row['streetname']['value'],
					"img" => $row['img']['value'],
					"link" => $row['cho']['value'],
					"year" => $row['date']['value']
					);
	}else{
		$col1[] = array(
					"street" => $row['street']['value'],
					"label" => $row['streetname']['value'],
					"img" => $row['img']['value'],
					"link" => $row['cho']['value'],
					"year" => $row['date']['value']
					);
	}
}

//print_r($col1);
?>
<html>
<head>
	
	<title>Amsterdams Pionieren!</title>

	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	
	<link href="https://fonts.googleapis.com/css?family=Nunito:300,700" rel="stylesheet">

	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

	<script
  src="https://code.jquery.com/jquery-3.2.1.min.js"
  integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4="
  crossorigin="anonymous"></script>

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
		img{
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
			color: #9E6B04;
			text-decoration: none;
		}
		a:hover{
			color: #000;
			text-decoration: none;
		}
		<?php if($title==1971){ ?>
			body{
				background-color: #F568FE;
			}
			img{
				border: 15px solid #5483FE;
				border-radius:40px;
			}
		<?php } ?>
		<?php if($title==1961){ ?>
			body{
				background-color: #000;
				color:#fff;
			}
			a,a:hover{
				color: #fff;
			}
			img{
				border: 2px solid #fff;
				border-radius:0;
			}
		<?php } ?>
	</style>

	
</head>
<body>


<div id="years">
	<a href="index.php?year=1880">1880's</a> | 
	<a href="index.php?year=1890">1890's</a> | 
	<a href="index.php?year=1900">1900's</a> | 
	<a href="index.php?year=1910">1910's</a> | 
	<a href="index.php?year=1920">1920's</a> | 
	<a href="index.php?year=1930">1930's</a> | 
	<a href="index.php?year=1940">1940's</a> | 
	<a href="index.php?year=1950">1950's</a> | 
	<a href="index.php?year=1960">1960's</a> | 
	<a href="index.php?year=1970">1970's</a> | 
	<a href="index.php?year=1980">1980's</a> | 
	<a href="index.php?year=1990">1990's</a> | 
	<a href="index.php?year=2000">2000's</a>
</div>

<div>
	<h1>Amsterdams Pionieren <?= $title ?>'s</h1>

	<p>
		Hieronder het antwoord van <a href="http://blogadamlink.nl/ontwikkelaar/lod-van-amsterdamse-erfgoedcollecties/">AdamNet</a> op de vraag 'geef alle afbeeldingen van straten die gemaakt zijn binnen twee jaar na aanleg van die straat'. We hopen daarmee een beeld van pionierend Amsterdam te kunnen laten zien, maar ook de mogelijkheden van het combineren van datasets (het <a href="https://adamlink.nl/geo/streets/list">stratenregister</a> met beginjaren van straten en de Amsterdamse collecties in AdamNet). Klik op de straatnaam voor een kaartje en meer foto's.
	</p>

	<div class="container-fluid">
		<div class="row">
			<div class="col-md-4">
				<?php foreach ($col1 as $cho) { ?>
					<a target="_blank" href="<?= $cho['link'] ?>"><img src="<?= $cho['img'] ?>" /></a>
					<h2><a href="straat.php?uri=<?= $cho['street'] ?>"><?= $cho['label'] ?></a> <?= $cho['year'] ?></h2>
				<?php } ?>
			</div>
			<div class="col-md-4">
				<?php foreach ($col2 as $cho) { ?>
					<a target="_blank" href="<?= $cho['link'] ?>"><img src="<?= $cho['img'] ?>" /></a>
					<h2><a href="straat.php?uri=<?= $cho['street'] ?>"><?= $cho['label'] ?></a> <?= $cho['year'] ?></h2>
				<?php } ?>
			</div>
			<div class="col-md-4">
				<?php foreach ($col3 as $cho) { ?>
					<a target="_blank" href="<?= $cho['link'] ?>"><img src="<?= $cho['img'] ?>" /></a>
					<h2><a href="straat.php?uri=<?= $cho['street'] ?>"><?= $cho['label'] ?></a> <?= $cho['year'] ?></h2>
				<?php } ?>
			</div>
		</div>
	</div>

	
</div>

<a target="_blank" href="<?= $querylink ?>">SPARQL it yourself &gt;</a>



<script>


</script>



</body>
</html>
