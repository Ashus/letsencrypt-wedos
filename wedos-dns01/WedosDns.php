<?php

namespace wedosDns01;

class WedosDns extends WedosWapi {
	/**
	 * Prida zaznam do DNS, vraci true/false nebo int s cislem chyby
	 * @param dnsRowAddRequest $request
	 * @return bool|int
	 * @throws WedosWapiResponseInvalidException
	 */
	public function dnsRowAdd(dnsRowAddRequest $request) {
		$result = $this->request('dns-row-add', get_object_vars($request));
		return $this->genericResponse($result);
	}

	/**
	 * Smaze zaznam z DNS podle ID, vraci true/false nebo int s cislem chyby
	 * @param dnsRowDeleteRequest $request
	 * @return bool|int
	 * @throws WedosWapiResponseInvalidException
	 */
	public function dnsRowDelete(dnsRowDeleteRequest $request) {
		$result = $this->request('dns-row-delete', get_object_vars($request));
		return $this->genericResponse($result);
	}

	/**
	 * Ziska seznam zaznamu z DNS
	 * @param dnsRowsListRequest $request
	 * @return dnsEntry[]|bool
	 * @throws WedosWapiResponseInvalidException
	 */
	public function dnsRowsList(dnsRowsListRequest $request) {
		$response = $this->request('dns-rows-list', get_object_vars($request));
		if (!$response)
			return false;
		if ($response->code != 1000)
			return false;
		return $response->data->row;
	}

	/**
	 * Potvrdi zmeny v DNS / urychli aplikovani zmen
	 * @param dnsDomainCommitRequest $request
	 * @return bool|int
	 * @throws WedosWapiResponseInvalidException
	 */
	public function dnsDomainCommit(dnsDomainCommitRequest $request) {
		$result = $this->request('dns-domain-commit', get_object_vars($request));
		return $this->genericResponse($result);
	}
}

class dnsRowAddRequest {
	/** @var string */
	public $domain;
	/** @var string */
	public $name;
	/** @var string */
	public $type;
	/** @var int */
	public $ttl;
	/** @var string */
	public $rdata;
}

class dnsRowDeleteRequest {
	/** @var string */
	public $domain;
	/** @var string */
	public $row_id;
}

class dnsRowsListRequest {
	/** @var string */
	public $domain;
}

class dnsDomainCommitRequest {
	/** @var string */
	public $name;
}

class dnsEntry {
	/** @var string */
	public $ID;
	/** @var string */
    public $name;
	/** @var int */
    public $ttl;
	/** @var string */
    public $rdtype;
	/** @var string */
    public $rdata;
	/** @var string */
    public $changed_date;
	/** @var string */
    public $author_comment;
}