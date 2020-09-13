<?php

require "../define.php";

function getval($mysqli, $query, $default=false, $err='****')
{
	$result = $mysqli->query($query);
	if($result === false)
	{
		return $err;
	}
	if($row = $result->fetch_row())
	{
		return $row[0];
	}
	return $default;
}

function getusname($mysqli, $us)
{
	$query = "select name from usname where id=$us";
	$result = $mysqli->query($query);
	if($result === false)
	{
		return '****';
	}
	if($row = $result->fetch_row())
	{
		return $row[0];
	}
	return $default;
}

if(isset($_GET['db']))
{
	$db = $_GET['db'];
}
else
{
	$db = DB;
}

$mysqli = new mysqli(DB_HOST, $user, $pass, $db, DB_PORT);
if($mysqli->connect_errno)
{
	die($mysqli->connect_error());
}
$mysqli->set_charset("utf8mb4") || die($mysqli->connect_error());

if(isset($_GET['user_id']))
{
	$user_id = $_GET['user_id'];

	$usname = getval($mysqli, "SELECT name FROM usname WHERE id=$user_id", null, false);
	if($usname === false)
	{
		$query = "CREATE TABLE usname(id int NOT NULL, name varchar(255) NOT NULL, PRIMARY KEY (id))";
		$mysqli->query($query);
	}
	if($usname === false || is_null($usname))
	{
		$usname = '(null)';
		$url = "https://ext.nicovideo.jp/thumb_user/$user_id";
		$file = file_get_contents($url);
		if(preg_match("|<a href=.*<strong>(.*?)</strong>.*</a>|i", $file, $matches) === 1)
		{
			$usname = $matches[1];
			$query = "INSERT INTO usname(id, name) VALUES($user_id, '$usname')";
			$mysqli->query($query);
		}
	}
}
else
{
	$user_id = false;
	$usname = '----';
}

if(isset($_GET['community']))
{
	$community = $_GET['community'];

	$coname = getval($mysqli, "SELECT name FROM coname WHERE id='$community'", null, false);
	if($coname === false)
	{
		$query = "CREATE TABLE coname(id char(12) NOT NULL, name varchar(255) NOT NULL, PRIMARY KEY (id))";
		$mysqli->query($query);
	}
	if($coname === false || is_null($coname))
	{
		$url = "https://com.nicovideo.jp/community/$community";
		$file = file_get_contents($url);

		$coname = '';
		preg_match("|<td class=\"content\">(20\d\d.*?)</td>|s", $file, $res);
		if(count($res) >= 2)
		{
			$coname .= trim($res[1]);
		}
		preg_match("|<h2 class=\"title\">.*?<a href=\"/community/co\d+\">(.*?)</a>|s", $file, $res);
		if(count($res) >= 2)
		{
			$coname .= ' '.trim($res[1]);
		}
		preg_match("|jp\/user\/\d+\" target=\"_blank\">(.*?)<\/a>|s", $file, $res);
		if(count($res) >= 2)
		{
			$coname .= '('.trim($res[1]).')';
		}
		if($coname !== '')
		{
			$query = "INSERT INTO coname(id, name) VALUES('$community', '$coname')";
			$mysqli->query($query);
		}
		else
		{
			$coname = '(null)';
		}
	}
}
else
{
	$community = false;
	$coname = '----';
}

$query = "SHOW DATABASES";
$result = $mysqli->query($query);
echo "<table border=\"1\">\n";
while($row = $result->fetch_assoc())
{
	$database = $row[Database];
	if(substr($database, 0, 3) === 'ccv')
	{
		if($database === $db)
		{
			echo "<th bgcolor=\"#ffc0c0\">\n";
		}
		else
		{
			echo "<th>\n";
		}
		echo "<a href=\".?db=$database\">$database</a>\n";
		echo "</th>\n";
	}
}
echo "</table>\n";

/*
$query = "SELECT * FROM user";
$result = $mysqli->query($query);
echo "<table border=\"1\">\n";
echo "<tr>\n";
echo "<th>id</th>\n";
echo "<th>timestamp</th>\n";
echo "<th>create_date</th>\n";
echo "<th>user_id</th>\n";
echo "<th>link</th>\n";
echo "<th>name</th>\n";
echo "<th>count</th>\n";
echo "<th>co</th>\n";
echo "<th>timestamp</th>\n";
echo "</tr>\n";
while($row = $result->fetch_assoc())
{
	echo "<tr>\n";
	echo "<td>$row[id]</td>\n";
	echo "<td>$row[timestamp]</td>\n";
	echo "<td>$row[create_date]</td>\n";
	$id = $row[user_id];
	if($id == $user_id)
	{
		echo "<td bgcolor=\"#ffc0c0\"><a href=\".?db=$db&user_id=$id\">$id</a></td>\n";
	}
	else
	{
		echo "<td><a href=\".?db=$db&user_id=$id\">$id</a></td>\n";
	}

	$user = getval($mysqli, "SELECT name FROM usname WHERE id=$id", '----', false);
	if($user === false)
	{
		$user = 'err';
	}
	echo "<td><a href=\"https://www.nicovideo.jp/user/$id\">$user</a></td>\n";

	echo "<td>".getval($mysqli, "SELECT name FROM $db.$id WHERE user_id=$id ORDER BY timestamp DESC LIMIT 1", '-')."</td>\n";
	echo "<td>".getval($mysqli, "SELECT COUNT(*) FROM $db.$id", '-')."</td>\n";
	echo "<td>".getval($mysqli, "SELECT COUNT(DISTINCT community) FROM $db.$id", '-')."</td>\n";
	echo "<td>".getval($mysqli, "SELECT timestamp FROM $db.$id ORDER BY timestamp DESC LIMIT 1", '-')."</td>\n";
	echo "</tr>\n";
}
echo "</table>\n";
*/

$query = "SELECT *, FROM_UNIXTIME(created_at) as created, FROM_UNIXTIME(updated_at) as updated FROM ".TB_MEMBER;
$result = $mysqli->query($query);
echo "<table border=\"1\">\n";
echo "<tr>\n";
echo "<th>id</th>\n";
echo "<th>user_id</th>\n";
echo "<th>user_name</th>\n";
echo "<th>count</th>\n";
echo "<th>rank</th>\n";
echo "<th>created_at</th>\n";
echo "<th>updated_at</th>\n";

echo "<th>name</th>\n";
echo "<th>count</th>\n";
echo "<th>co</th>\n";
echo "<th>timestamp</th>\n";
echo "</tr>\n";
while($row = $result->fetch_assoc())
{
	echo "<tr>\n";
	echo "<td align=\"right\">$row[id]</td>\n";
	$id = $row[user_id];
	if($id == $user_id)
	{
		echo "<td bgcolor=\"#ffc0c0\"><a href=\".?db=$db&user_id=$id\">$id</a></td>\n";
	}
	else
	{
		echo "<td><a href=\".?db=$db&user_id=$id\">$id</a></td>\n";
	}
	$user = $row[user_name];
	echo "<td><a href=\"https://www.nicovideo.jp/user/$id\">$user</a></td>\n";
	echo "<td align=\"right\">$row[count]</td>\n";
	echo "<td align=\"right\">$row[rank]</td>\n";
	echo "<td>$row[created]</td>\n";
	echo "<td>$row[updated]</td>\n";

	echo "<td>".getval($mysqli, "SELECT name FROM $db.$id WHERE user_id=$id ORDER BY updated_at DESC LIMIT 1", '-')."</td>\n";
	echo "<td align=\"right\">".getval($mysqli, "SELECT COUNT(*) FROM $db.$id", '-')."</td>\n";
	echo "<td align=\"right\">".getval($mysqli, "SELECT COUNT(DISTINCT community) FROM $db.$id", '-')."</td>\n";
	echo "<td>".getval($mysqli, "SELECT FROM_UNIXTIME(updated_at) FROM $db.$id ORDER BY updated_at DESC LIMIT 1", '-')."</td>\n";
	echo "</tr>\n";
}
echo "</table>\n";

if($user_id)
{
	echo "user_id:$user_id:$usname<br>\n";
	if(!$community)
	{
		$query = "SELECT community, COUNT(community) AS count FROM $db.$user_id GROUP BY community";
		$result = $mysqli->query($query);
		if($result === false)
		{
			die($query);
		}
		echo "<table border=\"1\">\n";
		echo "<tr>\n";
		echo "<th></th>\n";
		echo "<th>community</th>\n";
		echo "<th>link</th>\n";
		echo "<th>count</th>\n";
		echo "</tr>\n";
		$n = 0;
		while($row = $result->fetch_assoc())
		{
			echo "<tr>\n";
			echo "<td>$n</td>\n";
			$co = $row[community];
			echo "<td><a href=\".?db=$db&user_id=$user_id&community=$co\">$co</a></td>\n";

			$name = getval($mysqli, "SELECT name FROM coname WHERE id='$co'", '----');
			echo "<td><a href=\"https://com.nicovideo.jp/community/$co\" target=\"_blank\">$name</a></td>\n";

			echo "<td>$row[count]</td>\n";
			echo "</tr>\n";
			$n++;
		}
		echo "</table>\n";
	}
	else
	{
		echo "$community:<a href=\"https://com.nicovideo.jp/community/$community\" target=\"_blank\">$coname</a><br>\n";
		$query = <<<EOD
SELECT *,
	(UNIX_TIMESTAMP(now())-updated_at)/(60*60*24) as diffday,
	FROM_UNIXTIME(created_at) as created,
	FROM_UNIXTIME(updated_at) as updated
FROM `$user_id` WHERE community='$community' ORDER BY timestamp DESC
EOD;
		$result = $mysqli->query($query);
		if($result === false)
		{
			die($query);
		}
		echo "<table border=\"1\">\n";
		echo "<tr>\n";
		echo "<th></th>\n";
		echo "<th>id</th>\n";

	//	echo "<th>enable</th>\n";
		echo "<th>user_id</th>\n";
		echo "<th>name</th>\n";
	//	echo "<th>community</th>\n";
		echo "<th>comment</th>\n";
		echo "<th>visit</th>\n";
		echo "<th>point</th>\n";
	//	echo "<th>last_lv</th>\n";

	//	echo "<th>last_member</th>\n";
	//	echo "<th>last_visit</th>\n";
		echo "<th>created_at</th>\n";
	//	echo "<th>create_date</th>\n";
		echo "<th>updated_at</th>\n";
	//	echo "<th>timestamp</th>\n";
		echo "<th>diffday</th>\n";
		echo "</tr>\n";
		$n = 0;
		while($row = $result->fetch_assoc())
		{
			echo "<tr>\n";
			echo "<td align=\"right\">$n</td>\n";
			echo "<td align=\"right\">$row[id]</td>\n";

		//	echo "<td>$row[enable]</td>\n";
			echo "<td>$row[user_id]</td>\n";
			echo "<td>$row[name]</td>\n";
		//	echo "<td>$row[community]</td>\n";
			echo "<td align=\"right\">$row[comment_count]</td>\n";
			echo "<td align=\"right\">$row[visit_count]</td>\n";
			echo "<td align=\"right\">$row[visit_point]</td>\n";
		//	echo "<td>$row[last_lv]</td>\n";

			echo "<td>$row[created]</td>\n";
		//	echo "<td>$row[create_date]</td>\n";
			echo "<td>$row[updated]</td>\n";
		//	echo "<td>$row[timestamp]</td>\n";
			echo "<td align=\"right\">$row[diffday]</td>\n";

			echo "</tr>\n";
			$n++;
		}
		echo "</table>\n";
	}
}
$mysqli->close();

?>
