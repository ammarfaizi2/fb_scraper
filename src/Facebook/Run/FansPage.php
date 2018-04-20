<?php

namespace Facebook\Run;

use Facebook\DB;
use Facebook\Facebook;

class FansPage
{
	private $fp;

	public function __construct($fp)
	{
		$this->fp = $fp;
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
		$st = $this->fb->login();
		$st = "login_success";
		if ($st === "login_success") {
			print "Login success!\n\n";
			$pdo = DB::pdo();
			$stmt = $pdo->prepare(
				"INSERT INTO `posts` (`owner`, `post_fbid`, `post_url`, `text`, `files`, `scraped_at`) VALUES (:owner, :post_fbid, :post_url, :_text, :files, :scraped_at);"
			);
			print "Getting page timeline...\n\n\n";
			$i = 1;
			do {
				print "\nLoading page ".$i++."...\n";
				$st = $this->fb->go($this->fp);
				if ($pg = preg_match("/<a href=\"(.*)\">Show more/Usi", $st["out"], $mpg)) {
					$pg = explode("<a href=\"", $mpg[1]);
					$pg = end($pg);
					$pg = $this->fb->se($pg);
					$this->fp = $pg;
				}
				preg_match_all("/href=\"([^\#].*)\"/U", $st["out"], $m);
				$m = $m[1];
				$urls = [];
				foreach($m as $k => $m) {
					if (preg_match("/^\/a\/like.php.+ft_ent_identifier=(.*)\&/U", $m, $n)) {
						$urls[] = "https://m.facebook.com/".$n[1];
						continue;
					}
					if (preg_match("/^\/story.php\?story_fbid=(.*)\&/U", $m, $n)) {
						$urls[] = "https://m.facebook.com/".$n[1];	
						continue;
					}
					if (preg_match("/^\/[\w\d\.]+\/photos\/.+\/(.*)\//U", $m, $n)) {
						$urls[] = "https://m.facebook.com/".$n[1];	
						continue;
					}
					if (preg_match("/photo.php\?fbid=(.*)\&/U", $m, $n)) {
						$urls[] = "https://m.facebook.com/".$n[1];	
						continue;
					}
				}
				$urls = array_values(array_unique($urls));
				unset($urls[0]);
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
						$owner = "https://www.facebook.com/".rtrim($m[1], "/");
					} elseif (preg_match(
						"/<strong><a href=\"\/(.*)\?.+\&amp;__tn__=C-R\">/U", $st["out"], $m
					)) {
						$owner = "https://www.facebook.com/".rtrim($m[1], "/");
					}

					if (preg_match("/<strong .+<\/strong>(.+)<\/div>/U", $st["out"], $m)) {
						$text = trim($this->fb->se(strip_tags(str_replace("<br />", "\n", $m[1]))));
					} elseif(preg_match("/<title>(.*)<\/title>/Us", $st["out"], $m)) {
						$text = trim($this->fb->se(strip_tags(str_replace("<br />", "\n", $m[1]))));
					}

					$fbid = explode("/", $url);
					$fbid = end($fbid);

					$st = $this->fb->go("https://{$this->fb->prefix}.facebook.com/photo/view_full_size/?fbid={$fbid}");
					
					if (preg_match("/document.location.href=\"(.*)\"/U", $st["out"], $m)) {
						$files[] = json_decode("\"".$m[1]."\"");
					}

					if ($text == "") {
						$text = null;
					}

					print "OK\n";
					$data[] = $in = [
						"owner" => $owner,
						"post_fbid" => $fbid,
						"post_url" => $url,
						"files" => json_encode($files, JSON_UNESCAPED_SLASHES),
						"_text" => $text,
						"scraped_at" => date("Y-m-d H:i:s")
					];
					$stmt->execute($in);
				}
				
				
			} while (count($mpg) > 1);
			unset($stmt);
			print "\n\nTerminated!\n";
		} else {
			print $st."\n";
		}
	}
}
