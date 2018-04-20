<?php

namespace Facebook\Run;

use Facebook\DB;
use Facebook\Facebook;

class Group
{
	private $fb;

	private $group;

	private $groupOri;

	public function __construct($fp)
	{
		$this->group = "/groups/".$fp;
		$this->groupOri = $fp;
		$this->fb = new Facebook(FB_EMAIL, FB_PASS);
	}

	public static function run($fp)
	{
		$st = new self($fp);
		$st->action();
	}

	public function action()
	{
		print "Logging in...\n";
		// $st = $this->fb->login();
		$st = "login_success";		
		if ($st === "login_success") {
			$pdo = DB::pdo();
			$stmt = $pdo->prepare(
				"INSERT INTO `posts` (`owner`, `post_fbid`, `post_url`, `text`, `files`, `scraped_at`) VALUES (:owner, :post_fbid, :post_url, :_text, :files, :scraped_at);"
			);
			$i = 1;
			do {
				print "\nLoading page ".$i++."...\n";
				$st = $this->fb->go($this->group);
				
				if (preg_match("/<a href=\"(\/groups\/.+)\".+<span>See more posts/Usi", $st["out"], $mpg)) {
					$pg = explode("<a href=\"", $mpg[0]);
					$pg = end($pg);
					$pg = explode("\"", $pg, 2);
					$pg = $pg[0];
					$pg = $this->fb->se($pg);
					$this->group = $pg;

				}

				preg_match_all("/href=\"([^\#].*)\"/U", $st["out"], $m);
				$m = array_merge($m[0], $m[1]);
				
				$urls = [];
				
				foreach($m as $k => $m) {
					if (preg_match("/^\/groups\/(.*)\?/Usi", $m, $n) && !empty($n[1]) && preg_match("/[0-9]/", $n[1])) {
						$urls[] = "https://m.facebook.com/".$n[1];
					}
					if (preg_match("/^\/groups\/(.*)\?/Usi", $m, $n) && !empty($n[1]) && preg_match("/[0-9]/", $n[1])) {
						$urls[] = "https://m.facebook.com/".$n[1];
						continue;
					}
					if (preg_match("/^\/a\/like.php.+ft_ent_identifier=(.*)\&/U", $m, $n) && !empty($n[1]) && preg_match("/[0-9]/", $n[1])) {
						$urls[] = "https://m.facebook.com/".$n[1];
						continue;
					}
					if (preg_match("/^\/story.php\?story_fbid=(.*)\&/U", $m, $n) && !empty($n[1]) && preg_match("/[0-9]/", $n[1])) {
						$urls[] = "https://m.facebook.com/".$n[1];	
						continue;
					}
					if (preg_match("/^\/[\w\d\.]+\/photos\/.+\/(.*)\//U", $m, $n) && !empty($n[1]) && preg_match("/[0-9]/", $n[1])) {
						$urls[] = "https://m.facebook.com/".$n[1];	
						continue;
					}
					if (preg_match("/photo.php\?fbid=(.*)\&/U", $m, $n) && !empty($n[1]) && preg_match("/[0-9]/", $n[1])) {
						$urls[] = "https://m.facebook.com/".$n[1];	
						continue;
					}
				}
				foreach($urls as $k => $url) {
					if ($url == "https://m.facebook.com/".$this->groupOri) {
						unset($urls[$k]);
					}
				}
				$urls = array_values(array_unique($urls));
				$data = [];
				foreach($urls as $url) {
					print "[".date("Y-m-d H:i:s")."] Collecting data from ".str_replace("m.facebook", "www.facebook", $url)." ...";
					$st = $this->fb->go($url);
					$url = str_replace("m.facebook", "www.facebook", $url);
					$text = null;
					$owner = null;
					$files = [];
					if (preg_match(
						"/<div id=\"MPhotoContent\">.+<a href=\"\/(.*)\?.+\"._/Us", $st["out"], $m
					)) {
						$owner = "https://www.facebook.com/".$this->fb->se(rtrim($m[1], "/"));
					} elseif (preg_match(
						"/<strong><a href=\"\/(.*)\?.+\&amp;__tn__=C-R\">/U", $st["out"], $m
					)) {
						$owner = "https://www.facebook.com/".$this->fb->se(rtrim($m[1], "/"));
					}
					if (preg_match("/<div class=\"..\" style=\"\" data-ft=\".+\"><p>(.*)<\/div>/Usi", $st["out"], $m)) {
						$text = trim($this->fb->se(strip_tags(str_replace("<br />", "\n", $m[1]))));
					} elseif (preg_match("/<strong .+<\/strong>(.+)<\/div>/U", $st["out"], $m)) {
						$text = trim($this->fb->se(strip_tags(str_replace("<br />", "\n", $m[1]))));
					} elseif(preg_match("/<title>(.*)<\/title>/Us", $st["out"], $m)) {
						$text = trim($this->fb->se(strip_tags(str_replace("<br />", "\n", $m[1]))));
					}

					$fbid = explode("/", $url);
					$fbid = trim(end($fbid));

					$st = $this->fb->go("https://{$this->fb->prefix}.facebook.com/photo/view_full_size/?fbid={$fbid}");
					
					if (preg_match("/document.location.href=\"(.*)\"/U", $st["out"], $m)) {
						$files[] = json_decode("\"".$m[1]."\"");
					}

					if ($text == "") {
						$text = null;
					}

					
					$data[] = $in = [
						"owner" => $owner,
						"post_fbid" => $fbid,
						"post_url" => $url,
						"files" => json_encode($files, JSON_UNESCAPED_SLASHES),
						"_text" => $text,
						"scraped_at" => date("Y-m-d H:i:s")
					];
					$stmt->execute($in) and print "OK" or print "Skipped due to duplicate post";
					print "\n";
				}
			} while (count($mpg) > 1);
		} else {

		}
	}
}
