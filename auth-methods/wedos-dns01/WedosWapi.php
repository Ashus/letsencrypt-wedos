<?php

namespace wedosDns01;

class WedosWapi {
	private $login;
	private $wapiPass;

	public function __construct($login, $wapiPass) {
		$this->login = $login;
		$this->wapiPass = $wapiPass;
	}

	/**
	 * @return string
	 */
	private function generateHash() {
		return sha1($this->login . sha1($this->wapiPass) . (new \DateTime('now', new \DateTimeZone('Europe/Prague')))->format('H'));
	}

	/**
	 * @param string $command
	 * @param array $data
	 * @return object|Response
	 * @throws WedosWapiResponseInvalidException
	 */
	protected function request($command, $data) {
		$request = [
			'request' => [
				'user' => $this->login,
				'auth' => $this->generateHash(),
				'command' => $command,
				'data' => $data
			]
		];

		$url = 'https://api.wedos.com/wapi/json';

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'request=' . urlencode(json_encode($request)));
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 100);
		$res = curl_exec($ch);

		try {
			$data = @json_decode($res);
		} catch (\Exception $ex) {
			throw new WedosWapiResponseInvalidException();
		}
		if (isset($data->response))
			return $data->response;
		return null;
	}

	protected function genericResponse($result) {
		if (!$result)
			return false;
		if ($result->code == 1000)
			return true;
		return (int)$result->code;
	}
}

class Response {
	/** @var int */
	public $code;
	/** @var string */
	public $result;
	/** @var object */
	public $data;
}

class WedosWapiResponseInvalidException extends \Exception {
}
