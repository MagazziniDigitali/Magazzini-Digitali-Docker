<?php

require '../vendor/autoload.php';
require 'dockerSettings.php';
require 'dockerUtils.php';
require 'ticketInterface.php';

define("TICKET_LEN", 36);


class getParameterException extends Exception {

}

// Returns right container image for required type, exception raised if wrong or unset
function getRequestedContainerType ($type , $imagesAvailable ) {
	if ( (! isset( $type )) or (! in_array($type, array_keys($imagesAvailable) ) ) ){
		throw new getParameterException('Requested content type is unsupported or unset');
	}
	return $imagesAvailable["$type"];
}
// Returns ticket string from client call, exception raised if wrong or unset
function getRequestedTicket ($ticket) {
		if (! isset($ticket) ) {
			throw new getParameterException('No access ticket supplied');
		} elseif ( TICKET_LEN == mb_strlen($ticket)) {
			return $ticket;
		} else {
			throw new getParameterException('Supplied ticket is in an unknown format');
		}
}


///////////////////////////////////////
//                                   //
// Checks if user can access content //
//                                   //
///////////////////////////////////////

$isUserAuthorized = FALSE;
try {
	$ticket = getRequestedTicket ($_GET['ticket']);
} catch(getParameterException $e) {
	//print ( $e->getMessage() );
	header('Location: /error.php?err=1');
	exit();
}

try {
	$authenticationUserOutput = checkWsdlTicket($CheckTicketUrl, $CheckTicketUsername, $CheckTicketPassword, $ticket );
} catch(WsdlException $e) {
	//print ( $e->getMessage() );
	header('Location: /error.php?err=2');
	exit();
}

if (!empty($authenticationUserOutput->errorMsg)){
	print ("Error Msg: ".$authenticationUserOutput->errorMsg->errorType." - ".$authenticationUserOutput->errorMsg->msgError."\n");
	header('Location: /error.php?err=3');
 	exit();
} else {
	$isUserAuthorized = TRUE;
	$url = $authenticationUserOutput->url;
	try {
	$image = getRequestedContainerType($authenticationUserOutput->tipo, $imagesAvailable );
	} catch(getParameterException $e) {
		//print ( $e->getMessage() );
		header('Location: /error.php?err=4');
		exit();
	}
}

///////////////////////////////////
//                               //
//	Actually creating containers //
//                               //
///////////////////////////////////
if ( $isUserAuthorized ) {
	//Checks for firs available port in range
	try {
		$port = (string)port_check();
	} catch(dockerUtilsException $e) {
		//print ( 'Risorse terminate, riprova più tardi' );
		header('Location: /error.php?err=5');
		exit();
	}
	try {
		$containerId = createContainer($containerManager, $port, $url, $image);
	} catch(dockerUtilsException $e) {
		//print ( 'Il documento richiesto non può essere visualizzato,sigh'."\n".'Err: '.$e->getMessage() );
		header('Location: /error.php?err=6');
		exit();
	}
	try {
		$isStarted = startContainer($containerManager, $containerId);
	} catch(dockerUtilsException $e) {
		//print ('Il documento richiesto non può essere visualizzato, sigh'."\n".'Err: '.$e->getMessage());
		header('Location: /error.php?err=7');
		exit();
	}
	if ( $isStarted ){
		$src_url=$_SERVER["SERVER_NAME"];
		$webpage = "http://$src_url/vnc_auto.html?port=$port";
		header( 'Location: '.$webpage);
		}
}
?>
