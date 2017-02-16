<?php

class WsdlException extends Exception {

}

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
