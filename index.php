<?php
/**
 * Main file, all the other files are included here.
 *
 * Actually  do things, checks authorizations and then creates container
 *
 * @author svalo@libersoft.it
 * @copyright ???
 * @license   ???
 *
 */


require 'vendor/autoload.php';
require 'dockerSettings.php';
require 'dockerUtils.php';
require 'ticketInterface.php';

/**
 * User is not authorized by default.
 *
 * Checks if this page was called with a ticket and checks if it is in a valid format.
 *
 */
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

/**
 * Checks if the ticket is still valid according to the authentication system.
 *
 */

try {
    $authenticationUserOutput = checkWsdlTicket($CheckTicketUrl, $CheckTicketUsername, $CheckTicketPassword, $ticket );
} catch(WsdlException $e) {
	error_log( $e->getMessage() );
	header('Location: /error.php?err=2');
	exit();
}

/**
 * Checks whether we received error messages or no, if not user is authorized.
 * If user is authorized checks which image is needed for this request.
 *
 */
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


/**
 * If the user is authorized checks for the first available port.
 *
 */
if ( $isUserAuthorized ) {
	try {
		$port = (string)portCheck();
	} catch(dockerUtilsException $e) {
		error_log( 'Risorse terminate, riprova più tardi' );
		header('Location: /error.php?err=5');
		exit();
	}
    /**
     * Initilizes a container based on pre-defined parameters and the ones got from the auth. interface.
     *
     */
	try {
		$containerId = createContainer($containerManager, $port, $url, $image);
	} catch(dockerUtilsException $e) {
		error_log( 'Il documento richiesto non può essere visualizzato,sigh'."\n".'Err: '.$e->getMessage() );
		header('Location: /error.php?err=6');
		exit();
	}
    /**
     * Starts the given $containerId, if succesfull redirects the client to the appropriate page.
     *
     */
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
