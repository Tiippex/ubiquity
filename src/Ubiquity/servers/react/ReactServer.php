<?php

namespace Ubiquity\servers\react;

use Ubiquity\utils\http\headers\ReactHeaders;

class ReactServer {
	private $server;

	public function __construct($config, $basedir) {
		$headersInstance = new ReactHeaders ();

		$this->server = new \React\Http\Server ( function (\Psr\Http\Message\ServerRequestInterface $request) use ($config, $headersInstance, $basedir) {
			$_GET ['c'] = '';
			$uri = ltrim ( urldecode ( parse_url ( $request->getUri ()->getPath (), PHP_URL_PATH ) ), '/' );
			if ($uri == null || ! file_exists ( $basedir . '/../' . $uri )) {
				$_GET ['c'] = $uri;
			} else {
				$headers = $request->getHeaders ();
				return new \React\Http\Response ( 200, $headers, file_get_contents ( $basedir . '/../' . $uri ) );
			}

			$headers = $request->getHeaders ();
			$headersInstance->setRequest ( $request );
			$this->parseRequest ( $request->getUri ()->getPath (), $request->getMethod (), $request->getQueryParams (), $request->getCookieParams (), $request->getUploadedFiles (), $request->getServerParams (), $headers, $request->getParsedBody () );
			\ob_start ();
			\Ubiquity\controllers\Startup::setHeadersInstance ( $headersInstance );
			\Ubiquity\controllers\Startup::run ( $config );
			$content = ob_get_clean ();

			return new \React\Http\Response ( http_response_code (), $headers, $content );
		} );
	}

	public function run($port) {
		$loop = \React\EventLoop\Factory::create ();
		$socket = new \React\Socket\Server ( $port, $loop );
		$this->server->listen ( $socket );

		echo "Running react server at http://127.0.0.1:$port\n";

		$loop->run ();
	}

	public function parseRequest($uri, $method = 'GET', $parameters = [], $cookies = [], $files = [], $server = [], $headers = [], $parsedBody = []) {
		// end active session
		if (PHP_SESSION_ACTIVE === session_status ()) {
			// make sure open session are saved to the storage
			// in case the framework hasn't closed it correctly.
			session_write_close ();
		}
		// reset session_id in any case to something not valid, for next request
		session_id ( '' );
		// reset $_SESSION
		session_unset ();
		unset ( $_SESSION );

		$server = array_replace ( [
									'SERVER_NAME' => 'localhost',
									'SERVER_PORT' => 80,
									'HTTP_HOST' => 'localhost',
									'HTTP_USER_AGENT' => 'Ubiquity',
									'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
									'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
									'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
									'REMOTE_ADDR' => '127.0.0.1',
									'SCRIPT_NAME' => '',
									'SCRIPT_FILENAME' => '',
									'SERVER_PROTOCOL' => 'HTTP/1.1',
									'REQUEST_TIME' => time () ], $server );
		$server ['PATH_INFO'] = '';
		$server ['REQUEST_METHOD'] = strtoupper ( $method );
		$components = parse_url ( $uri );
		if (isset ( $components ['host'] )) {
			$server ['SERVER_NAME'] = $components ['host'];
			$server ['HTTP_HOST'] = $components ['host'];
		}
		if (isset ( $components ['scheme'] )) {
			if ('https' === $components ['scheme']) {
				$server ['HTTPS'] = 'on';
				$server ['SERVER_PORT'] = 443;
			} else {
				unset ( $server ['HTTPS'] );
				$server ['SERVER_PORT'] = 80;
			}
		}
		if (isset ( $components ['port'] )) {
			$server ['SERVER_PORT'] = $components ['port'];
			$server ['HTTP_HOST'] .= ':' . $components ['port'];
		}
		if (isset ( $components ['user'] )) {
			$server ['PHP_AUTH_USER'] = $components ['user'];
		}
		if (isset ( $components ['pass'] )) {
			$server ['PHP_AUTH_PW'] = $components ['pass'];
		}
		if (! isset ( $components ['path'] )) {
			$components ['path'] = '/';
		}
		switch (strtoupper ( $method )) {
			case 'POST' :
			case 'PUT' :
			case 'DELETE' :
				if (! isset ( $server ['CONTENT_TYPE'] )) {
					$server ['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
				}
			// no break
			case 'PATCH' :
				$query = [ ];
				break;
			default :
				$query = $parameters;
				break;
		}
		$queryString = '';
		if (isset ( $components ['query'] )) {
			parse_str ( html_entity_decode ( $components ['query'] ), $qs );
			if ($query) {
				$query = array_replace ( $qs, $query );
				$queryString = http_build_query ( $query, '', '&' );
			} else {
				$query = $qs;
				$queryString = $components ['query'];
			}
		} elseif ($query) {
			$queryString = http_build_query ( $query, '', '&' );
		}
		$server ['REQUEST_URI'] = $components ['path'] . ('' !== $queryString ? '?' . $queryString : '');
		$server ['QUERY_STRING'] = $queryString;
		if (isset ( $headers ['X-Requested-With'] ) && array_search ( 'XMLHttpRequest', $headers ['X-Requested-With'] ) !== false) {
			$server ['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		}
		if (strtoupper ( $method ) === 'POST') {
			$_POST = $parsedBody;
		}
		if (sizeof ( $parameters ) > 0) {
			$_GET = array_merge ( $_GET, $parameters );
		}
		$_SERVER = $server;
	}
}

