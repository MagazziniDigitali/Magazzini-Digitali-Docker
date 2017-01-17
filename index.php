<?php
require '../vendor/autoload.php';

use Docker\Docker;
use Docker\API\Model\ContainerConfig;
use Docker\API\Model\HostConfig;


//Default value for Firefox URL
$ffurl="http://www.bncf.firenze.sbn.it/";

//Initializes Docker object and instantiate a container manager
$docker = new Docker();
$containerManager = $docker->getContainerManager();

//Generate a container configuration with a base set of properties
function genContainerConfig ($ffurl="http://www.bncf.firenze.sbn.it/",
			     $port="5900") {

	//Define manager and components to set properties
	$containerConfig = new ContainerConfig();
	$containerHostConfig = new HostConfig();

	//Defines host settings
	$containerHostConfig->setMemory(268435456);
	$containerHostConfig->setMemorySwap(-1);
	$containerHostConfig->setCpuShares(512);
	$containerHostConfig->setPortBindings(["5900/tcp" => [["HostPort" => $port]]]);
	$containerHostConfig->setPublishAllPorts(False);
	$containerHostConfig->setPrivileged(False);
	$containerHostConfig->setExtraHosts([]);
	$containerHostConfig->setCapAdd("NET_ADMIN");
	$containerHostConfig->setCapDrop("MKNOD");
	$containerHostConfig->setNetworkMode("bridge");

	//Defines general container settings
	$containerConfig->setAttachStdin(False);
	$containerConfig->setAttachStdout(False);
	$containerConfig->setAttachStderr(False);
	$containerConfig->setTty(False);
	$containerConfig->setOpenStdin(False);
	$containerConfig->setStdinOnce(False);
	$containerConfig->setImage('local/ffviewer:latest');
	$containerConfig->setNetworkDisabled(False);
	$containerConfig->setExposedPorts(['5900/tcp' => [''=>'']]);
	$containerConfig->setCmd(
				["/usr/bin/x11vnc",
				"-input",
				"MBK,MBK",
				"-nopw",
				"-create",
				"-gone",
				"touch /root/left.txt",
				"-env",
				"FD_PROG=/usr/bin/viewer.sh $ffurl"]
				);
	$containerConfig->setHostConfig($containerHostConfig);

	return $containerConfig;
}

//Create a container with firefox url and port set
function create($containerManager, $port, $ffurl="http://www.bncf.firenze.sbn.it/"){

	//Sets the values for this instance
	$contConfig = genContainerConfig($ffurl, $port);

	$containerCreateResult = $containerManager->create($contConfig);

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
