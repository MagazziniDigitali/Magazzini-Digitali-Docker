<?php
require '../vendor/autoload.php';

use Docker\Docker;
use Docker\DockerClient;
use Docker\API\Model\ContainerConfig;
use Docker\API\Model\HostConfig;


//Default value for Firefox URL
$ffurl="http://www.bncf.firenze.sbn.it/";

//Available formats
$imagesAvailable=[
	'standard'	=> "local/ffviewer:standard",
	'ebook'		=> "local/ffviewer:ebook",
	'jwm'		=> "local/ffviewer:jwm"
	];

//Initializes Docker object and instantiate a container manager

$client = new DockerClient([
    'remote_socket' => 'tcp://127.0.0.1:2375/v1.24',
    'ssl' => false,
]);
$docker = new Docker($client);

//TODO:Check if docker is started
try {
	$containerManager = $docker->getContainerManager();
} catch (\Exception $e) {
	echo $e->getMessage();
}

//Generate a container configuration with a base set of properties
function genContainerConfig ($ffurl="http://www.bncf.firenze.sbn.it/",
			     $port="5900", $image="local/ffviewer:standard") {

	//Define manager and components to set properties
	$containerConfig = new ContainerConfig();
	$containerHostConfig = new HostConfig();

	//Defines host settings
	$containerHostConfig->setMemory(360000000);
	$containerHostConfig->setPortBindings(["5900/tcp" => [["HostPort" => $port]]]);
	$containerHostConfig->setPublishAllPorts(False);
	$containerHostConfig->setPrivileged(False);
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
	$containerConfig->setImage($image);
	$containerConfig->setNetworkDisabled(False);
	$containerConfig->setExposedPorts(['5900/tcp' => [''=>'']]);
	$containerConfig->setEnv(["FFURL=$ffurl"]);
	$containerConfig->setCmd(
				["/usr/bin/x11vnc",
				"-input",
				"MBK,MBK",
				"-timeout",
				"5",
				"-nopw",
				"-create",
				"-gone",
				"touch /root/left.txt",
				"-env",
				"FD_PROG=/usr/bin/viewer.sh"]
				);
	$containerConfig->setHostConfig($containerHostConfig);

	return $containerConfig;
}

//Create a container with firefox url and port set
function create($containerManager, $port, $ffurl="http://www.bncf.firenze.sbn.it/", $image){

	//Sets the values for this instance
	$contConfig = genContainerConfig($ffurl, $port, $image);

	$containerCreateResult = $containerManager->create($contConfig);

	if ( $containerCreateResult->getWarnings() == Null ) {
		return $containerCreateResult->getId();
	} else {
		return $containerCreateResult;
	}

};

//Start the container passed as ID
function start($containerManager, $id){
	$containerStartResult = $containerManager->start($id);
	$isStarted = $containerStartResult->getStatusCode();
	return $isStarted;
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
