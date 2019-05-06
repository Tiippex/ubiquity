<?php

namespace Ubiquity\utils\http\headers;

class PhpHeaders extends AbstractHeaders {

	public function getAllHeaders() {
		return \getallheaders();
	}

	public function header($key,$value,$replace=null,$http_response_code=null) {
		\header($key.': '.$value,$replace,$http_response_code);
	}
	public function headersSent(string &$file = null, int &$line = null) {
		return headers_sent($file,$line);
	}
	public function getInput() {
		$put = array ();
		\parse_str ( \file_get_contents ( 'php://input' ), $put );
		return $put;
	}


}

