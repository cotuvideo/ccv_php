<?php
class Namedb
{
	public $lv;
	public $co;
	public $tb;
	public $user_id;
	public $mysqli;
	public $name_array;
	public $comment_count;
	public $visit_count;
	public $visit_point;

	function __construct($lv, $co, $user_id, $nickname, $host, $user, $pass, $db, $port=3306)
	{
		$this->tb = getenv("CCV_TB_MEMBER");
		$this->lv = $lv;
		$this->co = $co;
		$this->user_id = $user_id;
		$this->mysqli = new mysqli($host, $user, $pass, $db, $port);
		if($this->mysqli->connect_errno)
		{
			die($this->mysqli->connect_error());
		}
		$this->mysqli->set_charset("utf8mb4") || die($mysqli->connect_error());

		$query = "SELECT * FROM $this->tb WHERE user_id=$user_id";
		$result = $this->mysqli->query($query);
		if($result === false)
		{
			exit("$query\n".$this->mysqli->error."\n");
		}
		if($row = $result->fetch_row())
		{
			echo "#[$user_id] id:".$row[0]."\n";
		}
		else
		{
			$len = mb_strlen($nickname);
			$user_name = '';
			for($i = 0; $i < $len; $i++)
			{
				$c = mb_substr($nickname, $i, 1);
				switch($c)
				{
				case "\\":
					$user_name .= "\\\\";
					break;
				case "'":
					$user_name .= "\'";
					break;
				default:
					$user_name .= $c;
					break;
				}
			}
			$now = time();
			$query = "INSERT INTO $this->tb(user_id, user_name, count, rank, created_at, updated_at) VALUES($user_id, '$user_name', 0, 0, $now, $now)";
			$result = $this->mysqli->query($query);
			if($result === false)
			{
				exit("$query\n".$this->mysqli->error."\n");
			}
		}

		$query = "show tables like '$user_id'";
		$result = $this->mysqli->query($query);
		if($result === false)
		{
			exit("$query\n".$this->mysqli->error."\n");
		}
		if($result->num_rows === 0)
		{
			$query = <<<SQL
CREATE TABLE `$user_id`(
	id int(11) NOT NULL AUTO_INCREMENT,
	timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	create_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	last_member timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	last_visit timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	user_id char(27) NOT NULL,
	community char(10) NOT NULL,
	enable bool NOT NULL DEFAULT true,
	name varchar(255) NOT NULL,
	comment_count int NOT NULL DEFAULT 1,
	visit_count int NOT NULL DEFAULT 1,
	visit_point int NOT NULL DEFAULT 0,
	last_lv char(12) NOT NULL,
	PRIMARY KEY (id, user_id)
) DEFAULT CHARSET=utf8mb4
SQL;
			$result = $this->mysqli->query($query);
			if($result === false)
			{
				exit("$query\n".$this->mysqli->error."\n");
			}

			$query = <<<SQL
ALTER TABLE `$user_id`
	drop last_member,
	drop last_visit,
	modify user_id varchar(27) not null,
	modify community varchar(10) not null,
	modify last_lv varchar(12) not null,
	add anonymity bool NOT NULL DEFAULT false after enable,
	add created_at int unsigned NOT NULL DEFAULT 0,
	add updated_at int unsigned NOT NULL DEFAULT 0,
	DROP PRIMARY KEY, ADD PRIMARY KEY (id),
	ADD UNIQUE INDEX (user_id, community)
SQL;
			$result = $this->mysqli->query($query);
			if($result === false)
			{
				exit("$query\n".$this->mysqli->error."\n");
			}
		}
		$this->name_array = array();
	}

	function addpoint($user_id, $premium, $anonymity, $score)
	{
		if($score !== 0)
		{
			return false;
		}

		$point = ($premium === 1) ? 2 : 1;
		for($i = 1; $i < 8; $i++)
		{
			if($this->visit_point < 1<<($i*4))
			{
				$point *= $i;
				break;
			}
		}
		if($anonymity == 0)
		{
			$query = "SELECT rank FROM ".TB_MEMBER." WHERE user_id=$user_id";
			$result = $this->mysqli->query($query);
			if($result !== false)
			{
				if($row = $result->fetch_assoc())
				{
					$rank = (int)$row['rank'];
					$point <<= $rank;
				}
			}
		}
		$this->visit_point += $point;
		return true;
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
				$set .= ",visit_count=$this->visit_count,last_lv='$this->lv'";
				if($this->addpoint($user_id, $premium, $anonymity, $score))
				{
					$set .= ",visit_point=$this->visit_point";
				}
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
				$this->comment_count = 1;
				$this->visit_count = 1;
				$this->visit_point = 0;
				$this->addpoint($user_id, $premium, $anonymity, $score);
				$query = "INSERT INTO `$this->user_id`(user_id, community, anonymity, name, visit_point, last_lv) VALUES('$user_id', '$this->co', $anonymity, '$name', $this->visit_point, '$this->lv')";
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
					if(AUTONAME === true)
					{
						if(isset($this->name_array[$user_id]))
						{
							$name = $this->name_array[$user_id];
						}
						else
						{
							if(isset($xml['no']))
							{
								$name = $xml['no']." ｺﾒ";
							}
							else
							{
								$name = $user_id;
							}
						}
					}
					else
					{
						$name = $user_id;
					}
				}
			}
		}

		$this->name_array[$user_id] = $name;
		return $name;
	}

	function isfirst($user_id)
	{
		return !isset($this->name_array[$user_id]);
	}

	function get_thumb_user($user_id)
	{
		$url = "https://ext.nicovideo.jp/thumb_user/".$user_id;
		$file = file_get_contents($url);
		if(preg_match("|<a href=.*<strong>(.*?)</strong>.*</a>|i", $file, $matches) === 1)
		{
			return $matches[1];
		}
		return $user_id;
	}
}
?>
