<?php
require '../vendor/autoload.php';
require 'dockerUtils.php';

//Default value for Firefox URL
$ffurl="http://www.bncf.firenze.sbn.it/";

//Available formats
$imagesAvailable=[
	'standard'	=> "local/ffviewer:standard",
	'ebook'		=> "local/ffviewer:ebook",
	'jwm'		=> "local/ffviewer:jwm"
	];


//Checks for firs available port in range
$port = (string)port_check();

if ( $port == "503" ) {
	echo "<h1>Server error</h1>";
	echo "Risorse terminate, riprovare fra qualche minuto";
}
else {
	// Maybe it's redundant, need to decide where to assign a default
	if (! isset($_POST['url'])){
			$url = "http://www.bncf.firenze.sbn.it/";
	}
	else {
		$url = $_POST['url'];
	}
	if (! isset($_POST['type'])){
		$image = $imagesAvailable['standard'];
	}
	else {
		if ( in_array($_POST['type'], array_keys($imagesAvailable) ) ){
			$image = $imagesAvailable[$_POST['type']];
		}
		else {
			$image = $imagesAvailable['standard'];
		}
	}
	//Some checks on POST url should be done
	$containerId = create($containerManager, $port, $url, $image);
	if ( $containerId  == "503") {
		echo "<h1>Server error</h1>";
		echo "<h3>Non posso creare il container</h3>";
		echo "Riprova fra poco o se il problema persiste contatta l'amministratore";
	} else {
		$isStarted = start($containerManager, $containerId);
		if ( $isStarted != "204" ){
			echo "<h1>Server error</h1>";
			echo "<h3>Non posso far partire il container</h3>";
			echo "Riprova fra poco o se il problema persiste contatta l'amministratore";
		} else {
			$src_url=$_SERVER["SERVER_NAME"];
			$webpage = "http://$src_url/vnc_auto.html?port=$port";
			echo "<script>window.onLoad = window.open('$webpage', '_self')</script>";
		}
	}
}
?>
