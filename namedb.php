<?php
class Namedb
{
	public $tb='user';
	public $user_id;
	public $mysqli;

	function __construct($user_id, $host, $user, $pass, $db, $port=3306)
	{
		$this->user_id = $user_id;
		$this->mysqli = new mysqli($host, $user, $pass, $db, $port);
		if($this->mysqli->connect_errno)
		{
			die($this->mysqli->connect_error());
		}
		$this->mysqli->set_charset("utf8") || die($mysqli->connect_error());

		$query = "SELECT * FROM $this->tb WHERE user_id=$user_id";
		$result = $this->mysqli->query($query);
		if($row = $result->fetch_row())
		{
			echo "#[$user_id] id:".$row[0]."\n";
		}
		else
		{
			$query = "INSERT INTO $this->tb(user_id) VALUES($user_id)";
			$result = $this->mysqli->query($query);
			$query = <<<SQL
CREATE TABLE `$user_id`(
	id int(11) NOT NULL AUTO_INCREMENT,
	timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	create_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	enable bool default true,
	user_id char(27) NOT NULL,
	name varchar(255) NOT NULL,
	PRIMARY KEY (id, user_id)
)
SQL;
			$result = $this->mysqli->query($query);
		}
	}

	function getname($user_id, $comment)
	{
		$pos0 = strpos($comment, '@');
		$pos1 = strpos($comment, '＠');
		if($pos0 !== false)
		{
			if($pos1 !== false)
			{
				$pos = ($pos0 > $pos1) ? $pos0+1 : $pos1+3;
			}
			else
			{
				$pos = $pos0+1;
			}
		}
		else
		{
			if($pos1 !== false)
			{
				$pos = $pos1+3;
			}
		}
		if(isset($pos))
		{
			$name = substr($comment, $pos);
		}

		$query = "SELECT name FROM `$this->user_id` WHERE user_id='$user_id'";
		$result = $this->mysqli->query($query);
		if($result === false)
		{
			return "** error **";
		}
		if($row = $result->fetch_row())
		{
			if(isset($name))
			{
				$query = "UPDATE `$user_id` SET name='$name' WHERE user_id='$user_id'";
				$result = $this->mysqli->query($query);
			}
			else
			{
				$name = $row[0];
			}
		}
		else
		{
			if(isset($name))
			{
				$query = "INSERT INTO `$this->user_id`(user_id,name) VALUES('$user_id','$name')";
				$result = $this->mysqli->query($query);
			}
			else
			{
				return $user_id;
			}
		}
		return $name;
	}
}
?>
