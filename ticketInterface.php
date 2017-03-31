<?php
/**
 * This file provides an interface to the wsdl serverlet.
 *
 * Here are defined the needed functions to communicate with the serverlet.
 * The serverlet is responsible of verifying if acces to a file is granted to a client owning a tocken and to return an address and a file type to
 * acceess it.
 *
 * @author svalo@libersoft.it
 * @copyright ???
 * @license   ???
 * @package VisualizzatoreRemoto\ticketInterface
 *
 */


/**
 * Exception is thrown if something goes wrong with the authentication.
 *
 */
class WsdlException extends Exception {

}

/**
 * Sends login and password to the authenticator
 *
 * @param string $url The URL of the authenticator wsdl endpoint
 * @param string $login The username
 * @param string $password The username password
 *
 * @return object The object containing the needed parameters to connect to the authentication interface
 *
 * @throws WsdlException "Riscontrato un errore nella verifica Software [errors] " in case of a SoapFault exception
 *
 */
function authenticationSoftware($url, $login, $password){
    try {
        $gsearch = new SoapClient($url);

        $params = array(
                'login' => $login,
                'password' => hash("sha256", $password));
        $result = $gsearch->AuthenticationSoftwareOperation($params);
    } catch (SoapFault $e) {
        throw new WsdlException('Riscontrato un errore nella verifica Software ['.$e->getMessage().']');
    }
    return $result;
}

/**
 * Parses the answer received from the wsdl and extract the URL of the ticketInterface
 *
 * @param object $software The object containing the needed parameters to connect to the authentication interface
 * @param string $param The object to access in the $software object
 *
 * @return string The URL to call to authenticate
 *
 */
function readParameter($software, $param){
    $result='';

    if (!empty($software->softwareConfig)){
        if (is_array($software->softwareConfig)){
            foreach($software->softwareConfig as $key => $value){
                if ($software->softwareConfig[$key]->nome == $param){
                    $checkTicketPortUrl = $software->softwareConfig[$param]->value;
                }
            }
        } else {
            if ($software->softwareConfig->nome == $param){
                $checkTicketPortUrl = $software->softwareConfig->value;
            }
        }
    }
    return $checkTicketPortUrl;
}

/**
 * Retrieves client IP using different methods
 *
 * @return string The client IP address
 *
 */
function getIpClient(){
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

/**
 * Interacts with the remote ticketInterface wsdl
 *
 * Calls the remote ticketInterface wsdl, authenticate the software making the call, checks if the provided ticket is still valid and associated with
 * the caller IP. If everything is fine returns an object containing the ticket, the url where to get the object from and the file type of the object.
 * If problems occourred an error message is returned or an exception is thrown.
 *
 * @param $url The URL to the wsdl endpoint
 * @param $login The username of the software
 * @param $password The password of the software
 * @param $ticket The ticket received by the client
 *
 * @return object The object containing the parameters to access the required object or the error message.
 *
 * @throws WsdlException "Riscontrato un errore nella verifica dell' oggetto [error]" if something went wrong during the verification of the object
 *
 */

function checkWsdlTicket($url, $login, $password, $ticket){
    $readParam = 'CheckTicketPort';
    try{
        $software = authenticationSoftware($url, $login, $password);
        $gsearch = new SoapClient(readParameter($software, $readParam));

        $params = array(
                'software' => $software,
                'ticket' => $ticket,
                'ipClient' => getIpClient()
                );

        $result = $gsearch->CheckTicketOperation($params);
    } catch (SoapFault $e) {
        throw new WsdlException('Riscontrato un errore nella verifica dell\'oggetto ['.$e->getMessage().']');
    } catch (WsdlException $e){
        throw $e;
    }
    return $result;
}
?>
