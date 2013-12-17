<?php

/**
 * @file plugins/generic/fidusWriter/FidusWriterConnection.inc.php
 *
 * Copyright (c) 2013 Simon Fraser University Library
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FidusWriterPlugin
 * @ingroup plugins_generic_fiduswriter
 *
 * @brief Encapsulates the connection between FidusWriter and OJS.
 */

class FidusWriterConnection {
	/** @var String apiUrl */
	var $apiUrl;

	/** @var String apiKey */
	var $apiKey;

	/**
	 * Constructor
	 * @param $apiUrl string The API URL
	 * @param $apiKey string The API key
	 */
	function FidusWriterConnection($apiUrl, $apiKey) {
		$this->apiUrl = $apiUrl;
		$this->apiKey = $apiKey;
	}

	/**
	 * Check that the API URL and key can be used.
	 * @return boolean True iff the API URL and key can be verified against the remote service.
	 */
	function verifyKey() {
		if (($ch = curl_init()) === false) return false;
		curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
			'apiKey' => $this->apiKey,
			'op' => 'validate',
		));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if (($result = curl_exec($ch)) === false) return false;
		curl_close($ch);
		return ($result === 'OK');
	}
}

?>
