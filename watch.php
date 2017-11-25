<?php

$self = $_SERVER['PHP_SELF'];

if(!isset($_COOKIE['user_session']))
{
	$pos = strrpos($self, '/')."\n";
	$url = substr($self, 0, $pos+1);
	header("Location: $url");
	exit;
}
$user_session = $_COOKIE['user_session'];

if(!isset($_GET['v']))
{
	echo "<form method=\"GET\" action=\"$self\">\n";
	echo "<input type=\"text\" name=\"v\"><br>\n";
	echo "<input type=\"submit\"><br>\n";
	echo "</form>\n";
	exit;
}
$v = $_GET['v'];

date_default_timezone_set('Asia/Tokyo');

$options = array('http'=>array('method'=>"GET", 'header'=>"Accept-language: ja\r\n"."Cookie: user_session=$user_session\r\n"));
$context = stream_context_create($options);
$url = "http://live.nicovideo.jp/api/getplayerstatus?v=$v";
$file = file_get_contents($url, false, $context) or die("read error $url");
$xml = simplexml_load_string($file);
$status = $xml['status'];
if($status == 'fail')
{
	header('Content-Type: text/xml; charset=utf-8');
	echo $xml->asXML();
	exit;
}

$title  = (string)$xml->stream->title;
$owner_name  = (string)$xml->stream->owner_name;
$watch_count  = (string)$xml->stream->watch_count;
$comment_count  = (string)$xml->stream->comment_count;
$base_time  = (int)$xml->stream->base_time;
$open_time  = (int)$xml->stream->open_time;
$start_time = (int)$xml->stream->start_time;
$end_time   = (int)$xml->stream->end_time;

$user_id = (int)$xml->user->user_id;

$addr = (string)$xml->ms->addr;
$port = (int)$xml->ms->port;
$thread = (int)$xml->ms->thread;

$options = array('http'=>array('method'=>"GET", 'header'=>"Accept-language: ja\r\n"."Cookie: user_session=$user_session\r\n"));
$context = stream_context_create($options);
$url = "http://watch.live.nicovideo.jp/api/getwaybackkey?thread=$thread";
$file = file_get_contents($url, false, $context) or die("read error $url");
$a = explode("=", $file);
$waybackkey = $a[1];

//header('Content-Type: text/plane; charset=utf-8');
//echo "addr=$addr port=$port thread=$thread<br>\n";
//echo "waybackeky=$waybackkey<br>\n";

$fp = fsockopen($addr, $port, $errno, $errstr) or die("fsockopen error");
stream_set_timeout($fp, 1);

$log = "";
$res_from = 16;//1000;
// 166
// 178
// 181
// 192
// 195
// 199
// 204
$when = time();
$chat = "";
$buf_no = 0;
for($i = 0; $i < 16; $i++)
{
	$content = "<thread thread=\"$thread\" version=\"20061206\" res_from=\"-$res_from\" scores=\"1\" when=\"$when\" waybackkey=\"$waybackkey\" user_id=\"$user_id\" />\0";
	fwrite($fp, $content);
	$res = "";
	for(;;)
	{
		$res .= fread($fp, 4096);
		$info = stream_get_meta_data($fp);
	//	echo "read_size:".strlen($res)." timed_out:".(int)$info['timed_out']." blocked:".(int)$info["blocked"]." eof:".(int)$info["eof"]." ".(int)feof($fp)."<br>\n";
		if(substr($res, -1) == "\0")
		{
			$xml = simplexml_load_string("<?xml version='1.0'?><root>".str_replace("\0", "\n", $res)."</root>");
			if($xml === false)
			{
				$chat .= "**** xml_error\n";
				echo "**** xml error ****<br>\n";
				echo str_replace("\0", "\n", $res);
				exit;
			}
			else
			{
				$last_res = (int)$xml->thread['last_res'];
				$no0 = (int)$xml->chat[0]['no'];
				$count = (int)$xml->chat->count();
			//	echo "last_res=$last_res no0=$no0 count=$count (".(($last_res-$no0+2)-$count).")<br>\n";
				if($last_res === (int)$xml->chat[$count-1]['no'])
				{
					break;
				}
			}
		}
		if($info['timed_out'])
		{
			header('Content-Type: text/plane; charset=utf-8');
			echo "**** time_out\n";
			echo $log;
			echo "res_from=$res_from buf_no=$buf_no\n";
			echo str_replace("\0", "\n", $res);
			for($i = $buf_no-1; $i >= 0; $i--)
			{
				echo "--------\n";
				echo $data[$i];
			}
			exit;
		}
	}
	$data[$buf_no++] = str_replace("\0", "\n", $res);
	$when = (int)$xml->chat[0]['date'];
	$log .= "when=$when no=$no0<br>\n";
//	file_put_contents("./tmp/$v.xml", $data[0]);
	if($no0 == 1)break;
}
//echo $log;

echo "<!DOCTYPE html>\n";
echo "<html lang=\"ja\">\n";
echo "<head>\n";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">\n";
echo "<title>コメントさん☆彡$title</title>\n";
echo "</head>\n";
echo "<body>\n";
echo "コメントさん☆彡<br>\n";

echo "<pre>\n";
echo $base_time;

echo $title."\n";
echo "[$owner_name]\n";
echo "base_time  time=$base_time " .date('Y/m/d(D) H:i:s', $base_time)."\n";
echo "open_time  time=$open_time " .date('Y/m/d(D) H:i:s', $open_time)."\n";
echo "start_time time=$start_time ".date('Y/m/d(D) H:i:s', $start_time)."\n";
echo "end_time   time=$end_time "  .date('Y/m/d(D) H:i:s', $end_time)."\n";
echo "watch_count:$watch_count\n";
echo "comment_count:$comment_count\n";
echo "user_id:$user_id\n";
//exit("#$buf_no ****************************************************************************");

//echo "------------------------------------------------<br>\n";
//echo "char($chat)\n";
		$start = new DateTime(date('Y/m/d H:i:s', $start_time));
		$ofs = $start_time-$base_time;
echo "ofs=$ofs\n";
//	$last_no = 0;
$nn=0;
echo "<table border=1>\n";
$line = 0;
$last_no = 0;
$comment_cnt = 1;
//		for($i = $this->cnt-1; $i >= 0; $i--)
while($buf_no > 0)
{
	$xml = simplexml_load_string("<?xml version='1.0'?><root>".$data[--$buf_no]."</root>");
//	echo  "<td>#last_res=".$xml->thread['last_res']."</td>\n";
//	echo  "@@".$xml->chat[0]['date']."\n";
	echo "<tr bgcolor=\"#ffffc0\"><td colspan=\"9\">-</td></tr>\n";
	foreach($xml->chat as $chat)
	{
		$no = (int)$chat['no'];
		$vpos = (int)$chat['vpos']-$ofs*100;
		$date = (int)$chat['date']-$start_time;
	//	$date_usec
	//	$mail
		$user_id = (string)$chat['user_id'];
		$premium   = (int)$chat['premium'];
		$anonymity = isset($chat['anonymity']) ? (int)$chat['anonymity'] : 0;
//		$locale
//:		$score = isset($chat['score']) ? (string)$chat['score'] : '';
		$text      = (string)$chat;

		{
			if($vpos >= 0)
			{
				$time = "";
			}
			else
			{
				$time = "-";
				$vpos = -$vpos;
			}
			$s = $vpos%6000;
			$m = $vpos/6000%60;
			$h = $vpos/(60*6000);
			$time .= sprintf("%2d:%02d:%02d.%02d ", $h, $m, $s/100, $s%100);
		}

		$name = $user_id;
//***************************************************************************
		if($anonymity == 0)
		{
			if(isset($name_tbl[$user_id]))
			{
				$name = $name_tbl[$user_id];
			}
			else
			{
				$name = "##$no##";
				$url = "http://ext.nicovideo.jp/thumb_user/".$user_id;
				$options = array("http"=>array("method"=>"GET", "header"=>"Accept-language: ja\r\n"));
				$context = stream_context_create($options);
				$file = file_get_contents($url, false, $context);
				preg_match("|<a href=.*<strong>(.*?)</strong>.*</a>|i", $file, $res);
				if(count($res) >= 2)
				{
					$name = $res[1];
				}
				$name_tbl[$user_id] = $name;
			}
		}
/***************************************************************************

	//	echo sprintf("%3d %s %-27s(%s) %5d ", $no, $time, $name, $anonymity, $score);
	//	echo $chat."\n";
***************************************************************************/

		while(++$last_no < $no)
		{
			echo "<tr>\n";
			echo "<td>$line</td>\n";
			echo "<td>$comment_cnt</td>\n";
			echo "<td>$last_no</td>\n";
			echo "<td colspan=\"4\"><font color=\"red\">ngコメント</font></td>\n";
			echo "</tr>\n";
			$comment_cnt++;
			$line++;
		}

		if(($premium&2) === 0)
		{
			$comment_no = $comment_cnt++;
		}
		else
		{
			$comment_no = "";
		}

		echo "<tr>\n";
		echo "<td>$line</td>\n";
		echo "<td>$comment_no</td>\n";
		echo "<td>$no</td>\n";
	//		echo "<td>$no(".($nn++).")</td>";
		echo "<td>$time</td>";
		echo "<td>";
		echo sprintf("%d:", $date/60/60);
		echo sprintf("%02d:", $date/60%60);
		echo sprintf("%02d", $date%60);
		echo "</td>";
	//		echo "<td align=\"right\">$score</td>\n";
		if($anonymity === 0)
		{
			echo "<td><a href=\"http://www.nicovideo.jp/user/$user_id\" target=\"_blank\">$name</a></td>\n";
		}
		else
		{
			echo "<td>$name</td>\n";
		}
		echo "<td>$premium</td>\n";
		echo "<td>$anonymity</td>\n";
		echo "<td>$text</td>\n";
		echo "</tr>\n";
		$line++;
	}
}
echo "</table>\n";
echo "</pre>\n";

echo "</body>\n";
echo "</html>\n";
?>
<?php
/*******************
class Live
{
	public $host = "192.168.0.23";
	public $user = "nico";
	public $db = "test";
	public $tb = "kote";

	public $time;

	public $cnt;
	public $chat;

	public function connect()
	{
		$this->mysqli = new mysqli($this->host, $this->user, '', $this->db);
		if($this->mysqli->connect_errno)
		{
			die($this->mysqli->connect_error);
		}
		$this->mysqli->query("set names utf8") or die($this->mysqli->error);
	}
	public function close()
	{
		$this->mysqli->close();
	}

	public function get($id)
	{
		$this->cnt = 0;
		for($i=0; $i<16; $i++)
		{
			$cnt=0;
			{
			}
//			$this->data[$i] = str_replace("\0", "\n", $content).str_replace("\0", "\n", $res);
			$xml = simplexml_load_string("<?xml version='1.0'?><root>".$this->data[$i]."</root>");
			file_put_contents("./lv.xml", str_replace("\0", "\n", $res));
			$this->chat .=  "#".$xml->thread['last_res']."\n";
			$this->chat .=  "#".$xml->chat[0]['date']."\n";
			$when = (int)$xml->chat[0]['date']+1;
			$this->cnt++;
			if($no0 == 1)break;
		}
	}
	public function put_comment()
	{
			{
				$query = "select name from $this->tb where user_id='$user_id' limit 1";
				$result = $this->mysqli->query($query) or die($this->mysqli->error);
				if($row = $result->fetch_assoc())
				{
					$name = $row['name'];
				}
				else
				{
					$name = $user_id;
				}

				if(0)
				{
					$date = (int)$chat['date'];
					$time = new DateTime(date('Y/m/d H:i:s', $date));
					$diff = $start->diff($time);
					echo sprintf("(%4d)", (int)$chat['no']);

					echo sprintf("%6d ", $vpos);
					echo "$time ";

					echo date('Y/m/d H:i:s ', (string)$chat['date']);
				//	echo date('[Y-m-d H:i:s] ' , $date-$this->start_time);
					echo $diff->format('%R %h:%I:%S ');
					echo sprintf("%-27s ", $name);
					echo $chat."\n";
				}
				else

					echo sprintf("<td>%d</td>", (int)$chat['no']);
					echo "<td>$time</td>";
				//	echo "<td>".date("Y/m/d H:i:s", (string)$chat['date'])."</td>";

					echo "<td align=\"right\">$score</td>\n";
				//	echo "<td>$name</td>\n";
					echo "<td><a href=\"http://www.nicovideo.jp/user/$user_id\" target=\"_blank\">$name</a></td>\n";
					echo "<td>$chat</td>\n";

					echo "</tr>\n";
				}
			}
		}
}

$host = "localhost";
$user = "nico";
$pass = "";
$db = "nico";
$tb = "live";

$lv->time = $_SERVER['REQUEST_TIME'];
$lv->get($id);

//$lv->put();
$lv->put_comment();

$lv->close();

//$query = "SELECT * FROM $db.$tb WHERE user=394 AND title IS NULL AND name IS NULL ORDER BY id DESC LIMIT 32";
//$result = $mysqli->query($query) or die($mysqli->error);

//while($row = $result->fetch_assoc())
{
}

//$result->free();
************************/
?>
