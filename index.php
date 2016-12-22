<?php 
require '../vendor/autoload.php';
use GuzzleHttp\Client;

$url = 'http://localhost:2375';
$client = new GuzzleHttp\Client(['base_uri' => $url]);
$ffurl="http://www.bncf.firenze.sbn.it/";

//Create a conrainer with firefox and port set
function create($port, $ffurl="http://www.bncf.firenze.sbn.it/"){
	
	global $client;
	global $url;	

	//Import and decode the base json
	$json_file = file_get_contents("create.json");
	$json_decoded = json_decode($json_file, $assoc = true);

	//Sets the values
	$json_decoded['Cmd'][8]="FD_PROG=/usr/bin/viewer.sh $ffurl";
	$json_decoded['HostConfig']['PortBindings']['5900/tcp'][0]['HostPort']="$port";

	$response = $client->request('POST','/containers/create', [
		'json' =>  $json_decoded,
		'timeout' => 5,
		'handler' => $tapMiddleware($clientHandler)
	]);

	if ($response->getStatusCode() != 201 ) {
		return "503";
	} else { 
		$container = ($response->json()['Id']);	
		return $container; 
	}
};

//Start the container passed as ID
function start($id){
	
	global $client;
	global $url;

	$response = $client->post($url.'/containers/'.$id.'/start');
	return $response->getStatusCode();
}

//Returns the firs available port starting from 5900
function port_check(){
	
	$host = "192.168.7.5";
	$port = "5900";
	$max_container = "250";
	$open = true;
	$max_port = $port+$max_container;
	while ( ($port < $max_port) && $open ) {
		$test = @fsockopen( $host, $port, $timeout = 3 );
		if ( !$test ){
			$open = false;
		}
		else{
			fclose( $test );
			$open = true;
			$port += 1;
		}
	}
	if ( $open ){
		return "503";
	}
	else{
		return $port;
	}
}

//End of function definition


$port = port_check();
if ( $port == "503" ) {
	echo "<h1>Server error</h1>";
	echo "Risorse terminate, riprovare fra qualche minuto";
}
else {
	if (! isset($_POST['url'])){
	$_POST['url']="http://www.bncf.firenze.sbn.it/";
	}
	
	$creato = create($port, $_POST['url']);
	if ( $creato == "503") {
		echo "<h1>Server error</h1>";
		echo "Riprova fra poco o se il problema persiste contatta l'amministratore";
	} else {
		$partito = start($creato);
		if ( $partito != "204" ){
			echo "<h1>Server error</h1>";
			echo "Riprova fra poco o se il problema persiste contatta l'amministratore";
		} else {
			$src_url=$_SERVER["SERVER_NAME"];
			$webpage = "http://$src_url/scripts/vnc_auto.html?host=$src_url&port=$port&encrypt=0&true_color=1";
			echo "<script>window.onLoad = window.open('$webpage', '_self')</script>";
		}
	}
}
?>
