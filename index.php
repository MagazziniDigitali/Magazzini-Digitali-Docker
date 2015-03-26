

<?php 
require 'vendor/autoload.php';
use GuzzleHttp\Client;

$client = new Client();
$url = 'http://192.168.7.5:2375';
$ffurl="http://www.bncf.firenze.sbn.it/";

//Create a conrainer with firefox and port set
function create($port, $ffurl="http://www.bncf.firenze.sbn.it/"){
	
	global $client;
	global $url;	
	global $pdfile;

	//Import and decode the base json
	$create_req = file_get_contents("create.json");
	$create_decoded = json_decode($create_req, $assoc = true);

	$cmd = ["x11vnc", "-input", "MBK,MBK", "-nopw", "-create", "-gone", "touch /root/left.txt", "-env", "FD_PROG=/usr/bin/viewer.sh $ffurl"];
	//Sets the values
	$create_decoded['HostConfig']['PortBindings']['5900/tcp'][0]['HostPort']="$port";
	$create_decoded['Cmd']=$cmd;

	$response = $client->post($url.'/containers/create', ['json' =>  $create_decoded, 'timeout' => 5 ] );

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
			$webpage = "http://docker.bncf.lan/scripts/vnc_auto.html?host=192.168.7.5&port=$port&encrypt=0&true_color=1";
			echo "<script>window.onLoad = window.open('$webpage', '_self')</script>";
		}
	}
}
?>
