<?php

namespace Facebook;

use PDO;

/**
* @author Ammar Faizi <ammarfaizi2@gmail.com>
*/
class DB
{
	private static $self;

	private $pdo;
	
	public function __construct()
	{
		$this->pdo = new PDO(
			"mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=".DB_PORT, DB_USER, DB_PASS
		);
	}

	public static function pdo()
	{
		return self::getInstance()->pdo;
	}

	public static function getInstance()
	{	
		if (self::$self === null) {
			self::$self = new self;
		}
		return self::$self;
	}
}