<?php
class Namedb
{
	public $lv;
	public $co;
	public $tb='user';
	public $user_id;
	public $mysqli;
	public $name_array;
	public $comment_count;
	public $visit_count;
	public $visit_point;

	function __construct($lv, $co, $user_id, $host, $user, $pass, $db, $port=3306)
	{
		$this->lv = $lv;
		$this->co = $co;
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
	last_member timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	last_visit timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	enable bool NOT NULL DEFAULT true,
	user_id char(27) NOT NULL,
	name varchar(255) NOT NULL,
	community char(10) NOT NULL,
	comment_count int NOT NULL DEFAULT 1,
	visit_count int NOT NULL DEFAULT 1,
	visit_point int NOT NULL DEFAULT 0,
	last_lv char(12) NOT NULL,
	PRIMARY KEY (id, user_id)
)
SQL;
			$result = $this->mysqli->query($query);
		}
		$this->name_array = array();
	}

	function getname(&$xml, $user_id, $comment)
	{
		$premium   = (int)$xml['premium'];
		$anonymity = (int)$xml['anonymity'];
		$score     = (int)isset($xml['score']) ? $xml['score'] : 0;

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
			if(strpos("01234567889０１２３４５６７８９", substr($comment, $pos, 1)) == false)
			{
				$name = substr($comment, $pos);
			}
		}

		$query = "SELECT * FROM `$this->user_id` WHERE user_id='$user_id' AND community='$this->co'";
		$result = $this->mysqli->query($query);
		if($result === false)
		{
			exit("\n$query\n".$this->mysqli->error."\n");
		}
		if($row = $result->fetch_assoc())
		{
			$last_lv = $row['last_lv'];
			$this->comment_count = $row['comment_count']+1;
			$this->visit_count = $row['visit_count'];
			$this->visit_point = $row['visit_point'];
			$set = "comment_count=$this->comment_count";
			if($last_lv != $this->lv)
			{
				$this->visit_count++;
				if($score == 0)
				{
					$point = ($premium === 1) ? 2 : 1;
					$this->visit_point += $point;
					$set .= ",visit_count=$this->visit_count,visit_point=$this->visit_point";
				}
				else
				{
					$point = 0;
				}
				$set .= ",last_lv='$this->lv'";
			}
			if(isset($name))
			{
				$set .= ",name='$name'";
			}
			else
			{
				$name = $row['name'];
			}
			$id = $row['id'];
			$query = "UPDATE `$this->user_id` SET $set WHERE id='$id'";
			$this->mysqli->query($query) || die("\n$query\n".$this->mysqli->error."\n");
		}
		else
		{
			$query = "SELECT name FROM `$this->user_id` WHERE user_id='$user_id'";
			$result = $this->mysqli->query($query);
			if($result === false)
			{
				exit("\n$query\n".$this->mysqli->error."\n");
			}
			if($row = $result->fetch_assoc())
			{
				$name = $row['name'];
			}
			if(isset($name))
			{
				if($score == 0)
				{
					$point = ($premium === 1) ? 2 : 1;
				}
				else
				{
					$point = 0;
				}
				$this->comment_count = 1;
				$this->visit_count = 1;
				$this->visit_point = $point;
				$query = "INSERT INTO `$this->user_id`(user_id,name,community,visit_point,last_lv) VALUES('$user_id','$name', '$this->co', $point, '$this->lv')";
				$this->mysqli->query($query) || die("\n$query\n".$this->mysqli->error."\n");
			}
			else
			{
				if(AUTOGET === true && $anonymity === 0)
				{
					$name = $this->get_thumb_user($user_id);
				}
				else
				{
					$name = $user_id;
				}
			}
		}

		return $name;
	}

	function get_thumb_user($user_id)
	{
		$url = "http://ext.nicovideo.jp/thumb_user/".$user_id;
		$file = file_get_contents($url);
		if(preg_match("|<a href=.*<strong>(.*?)</strong>.*</a>|i", $file, $matches) === 1)
		{
			return $matches[1];
		}
		return $user_id;
	}
}
?>
