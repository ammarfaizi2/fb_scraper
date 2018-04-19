<?php

namespace Facebook;

use Facebook\HttpStream;
use Facebook\FacebookException;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com>
 * @license MIT
 * @package Facebook
 * @version 0.0.1
 */
class Facebook
{
	use HttpStream;

	/**
	 * @var string
	 */
	private $prefix = "m";

	/**
	 * @var string
	 */
	private $email;

	/**
	 * @var string
	 */
	private $pass;

	/**
	 * @var string
	 */
	private $cookieFile;

	/**
	 * Constructor.
	 *
	 * @param string $email
	 * @param string $pass
	 * @param string $cookieFile
	 * @throws \Facebook\FacebookException
	 * @return void
	 */
	public function __construct($email, $pass, $cookieFile = null)
	{
		$this->email = $email;
		$this->pass = $pass;
		$this->cookieFile = is_null($cookieFile) ? getcwd()."/cookie.txt" : $cookieFile;

		if (! file_exists($this->cookieFile)) {
			fclose(fopen($this->cookieFile, "w"));
		}

		if (! file_exists($this->cookieFile)) {
			throw new FacebookException("Could not create cookie file in ".$this->cookieFile);
		}
	}

	/**
	 * @return string
	 */
	public function login()
	{
		// $st = $this->go("https://{$this->prefix}.facebook.com/login.php?fl=1");
		// file_put_contents("login.tmp", $st["out"]);
		$st["out"] = file_get_contents("login.tmp");
		if (preg_match("/<form.+method=\"post\".+action=\"(.*)\".+>(.*)<\/form>/U", $st["out"], $m)) {
			$action = self::se($m[1]);
			if (preg_match_all("/<input type=\"hidden\".+>/U", $m[2], $m)) {
				$posts = [];
				foreach ($m[0] as $m) {
					if (preg_match("/name=\"(.*)\"/U", $m, $n)) {
						if (preg_match("/value=\"(.*)\"/U", $m, $o)) {
							$posts[self::se($n[1])] = self::se($o[1]);
						} else {
							$posts[self::se($n[1])] = "";
						}
					}
				}
				$posts["email"] = $this->email;
				$posts["pass"] = $this->pass;
				$posts["login"] = "Login";
				$this->go($action, 
					[
						CURLOPT_POST => true,
						CURLOPT_POSTFIELDS => http_build_query($posts)
					]
				);
			}
		}
		$rawCookie = $this->getRawCookie();
		if (preg_match("/checkpoint/", $rawCookie))
			return "checkpoint";
		if (preg_match("/c_user/", $rawCookie))
			return "login_success";
		return "login_failed";
	}

	/**
	 * @return string
	 */
	public function getRawCookie()
	{
		return file_get_contents($this->cookieFile);
	}

	/**
	 * @param string $string
	 * @return string
	 */
	public static function se($string)
	{
		return html_entity_decode($string, ENT_QUOTES, "UTF-8");
	}

	/**
	 * @param string $url
	 * @param array  $opt
	 * @return array
	 */
	public function go($url, $opt = [])
	{
		if (preg_match("/^http.+/", $url)) {
			$url = $url;
		} else {
			$url = "https://{$this->prefix}.facebook.com/".ltrim($url, "/");
		}
		$ch = curl_init($url);
		$optf = [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_COOKIEFILE => $this->cookieFile,
			CURLOPT_COOKIEJAR => $this->cookieFile,
			CURLOPT_USERAGENT => "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:55.0) Gecko/20100101 Firefox/55.0",
		];
		foreach($opt as $k => $opt) {
			$optf[$k] = $opt;
		}
		curl_setopt_array($ch, $optf);
		$out = curl_exec($ch);
		$info = curl_getinfo($ch);
		$errno = curl_errno($ch);
		$error = curl_error($ch);
		curl_close($ch); 
		return [
			"out" => $out,
			"info" => $info,
			"errno" => $errno,
			"error" => $error
		];
	}
}
