<?php

require '../vendor/autoload.php';

use Docker\Docker;
use Docker\DockerClient;
use Docker\API\Model\ContainerConfig;
use Docker\API\Model\HostConfig;

define( "DOCKER_HOST",  "192.168.7.5");
define( "MAX_CONTAINER","250");
define( "FIRS_PORT",	"5900");
define( "FIREFOXURL", "http://md-www.test.bncf.lan/index.php/opac/");
define( "REMOTE_SOCKET", "tcp://127.0.0.1:2375/v1.24");

class dockerUtilsException extends Exception {

}

//Initializes Docker object and instantiate a container manager

$client = new DockerClient([
    'remote_socket' => REMOTE_SOCKET,
    'ssl' => false,
]);
$docker = new Docker($client);

//TODO:Check if docker is started
try {
	$containerManager = $docker->getContainerManager();
} catch (\Exception $e) {
	print ( $e->getMessage() );
}
//Generate a container configuration with a base set of properties
function genContainerConfig ($ffurl=FIREFOXURL,
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
function createContainer($containerManager, $port, $ffurl=FIREFOXURL, $image){

	//Sets the values for this instance
	//TODO: cath errors
	$contConfig = genContainerConfig($ffurl, $port, $image);

	$containerCreateResult = $containerManager->create($contConfig);

	if ( $containerCreateResult->getWarnings() == Null ) {
		return $containerCreateResult->getId();
	} else {
		throw new dockerUtilsException( 'Failed to create container: '.$containerCreateResult);
	}

};

//Start the container passed as ID
function startContainer($containerManager, $id){
	$containerStartResult = $containerManager->start($id);
	$isStarted = $containerStartResult->getStatusCode();
	switch ($isStarted) {
		case 204:
			return TRUE;
		case 304:
			throw new dockerUtilsException ('Container already started');
		case 404:
			throw new dockerUtilsException ('Container does not exist');
		case 500:
			throw new dockerUtilsException ('Generic server error');
	}
}

//Returns the firs available port starting from 5900
function port_check(){

	$host = DOCKER_HOST;
	$port = FIRS_PORT;
	$max_container = MAX_CONTAINER;
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
		throw new dockerUtilsException('No available port left');
	}
	else{
		return $port;
	}
}
?>
