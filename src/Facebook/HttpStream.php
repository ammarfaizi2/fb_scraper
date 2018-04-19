<?php

namespace Facebook;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com>
 * @license MIT
 * @package Facebook
 * @version 0.0.1
 */
trait HttpStream
{
	/**
	 * @var string
	 */
	private $responseBody;

	/**
	 * @param bool $rewriteUrl
	 * @return void
	 */
	public function browserStream($rewriteUrl = false)
	{
		if (isset($_COOKIE["prefix"])) {
			$this->prefix = $_COOKIE["prefix"];
		}

		if ($rewriteUrl) {
			
		} else {
			$this->noRewrite();
		}

		$this->fixResponseBody();
		$this->sendResponseBody();
	}

	/**
	 * @return void
	 */
	private function fixResponseBody()
	{
		if (!empty($this->info["redirect_url"])) {
			if (preg_match("/^https:\/\/(.*).facebook.com/U", $this->info["redirect_url"], $m)) {
				setcookie("prefix", $m[1], time()+3600*24*90, "/");
			}
			header("location:?url=".urlencode(rawurlencode($this->info["redirect_url"])));
			return;
		}

		if (preg_match_all("/href=\"([^\#]*)\"/U", $this->responseBody, $m)) {
			$r1 = $r2 = [];
			foreach($m[1] as $m) {
				$r1[] = $m;
				$r2[] = "?url=".urlencode(rawurlencode(self::se($m)));
			}
			$this->responseBody = str_replace($r1, $r2, $this->responseBody);
		}

		if (preg_match_all("/<form.+action=\"(.*)\".+>/U", $this->responseBody, $m)) {
			$r1 = $r2 = [];
			foreach($m[1] as $m) {
				$r1[] = $m;
				$r2[] = "?url=".urlencode(rawurlencode(self::se($m)));
			}
			$this->responseBody = str_replace($r1, $r2, $this->responseBody);
		}
		
	}

	/**
	 * @return void
	 */
	private function sendResponseBody()
	{
		echo $this->responseBody;
	}

	/**
	 * @return void
	 */
	private function noRewrite()
	{
		if (isset($_SERVER["REQUEST_URI"]) and preg_match("/sem_pixel/", $_SERVER["REQUEST_URI"])) {
			exit;
		}
		$url = isset($_GET["url"]) ? rawurldecode($_GET["url"]) : "";
		if (isset($_SERVER["REQUEST_METHOD"])) {
			$method = $_SERVER["REQUEST_METHOD"];
		} else {
			$method = "GET";
		}
		if ($method == "POST") {
			$st = $this->go($url,
				[
					CURLOPT_POST => true,
					CURLOPT_POSTFIELDS => http_build_query($_POST),
					CURLOPT_FOLLOWLOCATION => false,
					CURLOPT_REFERER => (! empty($_COOKIE["old_url"]) ? $_COOKIE["old_url"] : "")
				]
			);
		} else {
			$st = $this->go($url,
				[
					CURLOPT_FOLLOWLOCATION => false,
					CURLOPT_REFERER => (! empty($_COOKIE["old_url"]) ? $_COOKIE["old_url"] : "")
				]
			);
		}
		$this->responseBody = $st["out"];
		$this->info = $st["info"];
		setcookie("old_url", $this->info["url"], time()+3600*24*90);
	}
}
