<?php

require 'vendor/autoload.php';
require 'dockerSettings.php';
require 'dockerUtils.php';
require 'ticketInterface.php';


///////////////////////////////////////
//                                   //
// Checks if user can access content //
//                                   //
///////////////////////////////////////

$isUserAuthorized = FALSE;
try {
    if (!isset($_GET['idTicket'])){
        throw new getParameterException('Token can\'t be empty');
    }
    $ticket = getRequestedTicket ($_GET['idTicket']);
} catch(getParameterException $e) {
	error_log( $e->getMessage() );
	header('Location: /error.php?err=1');
	exit();
}

try {
    $authenticationUserOutput = checkWsdlTicket($CheckTicketUrl, $CheckTicketUsername, $CheckTicketPassword, $ticket );
} catch(WsdlException $e) {
	error_log( $e->getMessage() );
	header('Location: /error.php?err=2');
	exit();
}

if (!empty($authenticationUserOutput->errorMsg)){
	error_log("Error Msg: ".$authenticationUserOutput->errorMsg->errorType." - ".$authenticationUserOutput->errorMsg->msgError."\n");
	header('Location: /error.php?err=3');
 	exit();
} else {
	$isUserAuthorized = TRUE;
	$url = $authenticationUserOutput->url;
	try {
	$image = getRequestedContainerType($authenticationUserOutput->tipo, $imagesAvailable );
	} catch(getParameterException $e) {
		error_log( $e->getMessage() );
		header('Location: /error.php?err=4');
		exit();
	}
}

///////////////////////////////////
//                               //
// Actually creating containers  //
//                               //
///////////////////////////////////
if ( $isUserAuthorized ) {
	//Checks for firs available port in range
	try {
		$port = (string)portCheck();
	} catch(dockerUtilsException $e) {
		error_log( 'Risorse terminate, riprova più tardi' );
		header('Location: /error.php?err=5');
		exit();
	}
	try {
		$containerId = createContainer($containerManager, $port, $url, $image);
	} catch(dockerUtilsException $e) {
		error_log( 'Il documento richiesto non può essere visualizzato,sigh'."\n".'Err: '.$e->getMessage() );
		header('Location: /error.php?err=6');
		exit();
	}
	try {
		$isStarted = startContainer($containerManager, $containerId);
	} catch(dockerUtilsException $e) {
		error_log('Il documento richiesto non può essere visualizzato, sigh'."\n".'Err: '.$e->getMessage());
		header('Location: /error.php?err=7');
		exit();
	}
	if ( $isStarted ){
		$src_url=$_SERVER["SERVER_NAME"];
		$webpage = "http://$src_url/vnc.html?port=$port";
		header( 'Location: '.$webpage);
		}
}
?>
