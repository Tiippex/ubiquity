<?php

namespace Ubiquity\utils\http\headers;

abstract class AbstractHeaders {
	public abstract function getAllHeaders();
	public abstract function header($key,$value,$replace=null,$http_response_code=null);
	public abstract function headersSent(string &$file = null, int &$line = null);
	public abstract function getInput();
}

