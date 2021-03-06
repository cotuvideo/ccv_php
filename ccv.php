<?php

require 'define.php';
require 'getplayerstatus.php';
require 'namedb.php';
require 'commentreader.php';

$line = 0;
$comment_cnt = 0;
$last_no = 0;

$last_res = 0;

function getnow()
{
	global $watch_start_time, $watch_seek_time;
	return (microtime(true)-$watch_start_time)*SPEED/4+$watch_seek_time;
}

class Ccv
{
	public $no;

	function __construct()
	{
		global $watch_start_time, $watch_seek_time;

		$watch_start_time = microtime(true);
		$watch_seek_time = 0;
	}

	function get_date($date)
	{
		if($date < 0)
		{
			$date = -$date;
			$sign = '-';
		}
		else
		{
			$sign = '';
		}
		return sprintf("$sign%d:%02d:%02d", $date/60/60, $date/60%60, $date%60);
	}

	function put_comment($xml, $name, $text, $col=0)
	{
		global $namedb;
		global $line;
		global $start_time, $archive;
		global $comment_cnt;

		$no        = $this->no;
//		$vpos
		$date      = $this->get_date((int)$xml['date']-$start_time);
//		$date_usec
//		$mail
		$premium   = (int)$xml['premium'];
		$anonymity = (int)$xml['anonymity'];
//		$locale
		$score     = sprintf("%5s", isset($xml['score']) ? $xml['score'] : '');

		$c_count = $namedb->comment_count;
		$v_count = $namedb->visit_count;
		$v_point = $namedb->visit_point;

		if(($premium&2) !== 0)
		{
			$comment_no = '';
		}
		else
		{
			$comment_no = $comment_cnt++;
		}

		$p = ($premium >= 0 && $premium <= 3) ? substr(' pox', $premium, 1) : '*';
		$a = ($anonymity >= 0 && $anonymity <= 1) ? substr(' a', $anonymity, 1) : '*';
		echo sprintf("\033[K%4d|%4d|%4s|%s|%s%s|%6s|%4s|%3s|%3s|%-27s", $line++, $no, $comment_no, $date, $p, $a, $score, $c_count, $v_count, $v_point, $name);
		$pos = 63+11;
		if($col === 0)
		{
			echo sprintf("\r\033[%dC\033[K%s\n", $pos, $text);
		}
		else
		{
			echo sprintf("\r\033[%dC\033[K\033[%dm%s\033[m\n", $pos, $col, $text);
		}
		echo get_time()."\r";
	}
}

function get_time()
{
	global $watch_start_time, $watch_seek_time;
	global $start_time, $end_time;
	global $watch_count, $comment_count;

	$time = (int)getnow();
	$info = gmdate("H:i:s", $time).gmdate("/H:i:s", $end_time-$start_time);
	return sprintf("%s w:%4d c:%4d %s", $info, $watch_count, $comment_count, date("m-d H:i:s", $start_time+$time));
}

function put_comment($str)
{
	global $ccv;
	global $start_time, $archive;
	global $line;
	global $comment_cnt;
	global $last_no;
	global $last_res;
	global $watch_start_time, $watch_seek_time;
	global $namedb;

	$xml = new SimpleXMLElement($str);

	$tagName = $xml->getName();
	if($tagName === "thread")
	{
		$last_res = (int)$xml['last_res'];
		echo "last_res=$last_res\n";
		echo get_time()."\r";
		return false;
	}
	if($tagName !== "chat")
	{
		echo "unknown tag name $tagName\n";
		return false;
	}

	$no        = (int)$xml['no'];
	$date      = (int)$xml['date']-$start_time;
	$user_id   = (string)$xml['user_id'];
	$premium   = (int)$xml['premium'];
	$anonymity = (int)$xml['anonymity'];
	$text      = (string)$xml;

	$namedb->comment_count = '';
	$namedb->visit_count = '';
	$namedb->visit_point = '';

	if($last_no === 0)$last_no = $no-1;
	while(++$last_no < $no)
	{
		$ccv->no = $last_no;
		$ccv->put_comment($xml, '', "\033[31mng commnet\033[m");
	}
	if(($premium&2) !== 0 && substr($text, 0, 12) == '/hb ifseetno')
	{
		return false;
	}

	if($archive == 1)
	{
		while(1)
		{
			$wait = (float)($date-getnow());
			if($wait <= 0)break;
			if($wait < 1.0)
			{
				usleep($wait*1000000);
				echo get_time()."\r";
				break;
			}
			usleep(1000000);
			echo get_time()."\r";
		}
	}
	$col = 0;
	if($premium === 2 || ($premium === 3 && $user_id === '900000000'))
	{
		$name = $user_id;
	}
	else
	{
		if($premium === 3)
		{
			$col = OWNERCOLOR;
			if($anonymity == 0)
			{
				$name = "放送者($user_id)";
			}
			else
			{
				$name = $user_id;
			}
		}
		else
		{
			if($namedb->isfirst($user_id))
			{
				$col = FIRSTCOLOR;
			}
			$name = $namedb->getname($xml, $user_id, $text);
		}
	}
	$ccv->no = $no;
	$ccv->put_comment($xml, $name, $text, $col);

	if($archive == 1)
	{
		if($no >= $last_res)
		{
			echo "\n** last_res **\n";
			return true;
		}
	}
	else
	{
		if($premium == 2 && $anonymity == 1 &&  $text == '/disconnect')
		{
			echo "\n** disconnect **\n";
			return true;
		}
	}

	return false;
}

	date_default_timezone_set('Asia/Tokyo');

	if(count($argv) < 3)exit;
	$ccv = new Ccv();
	$id = $argv[1];

	$user_session = $argv[2];
	$playerstatus = getplayerstatus($id, $user_session);
	if($playerstatus === false)
	{
		echo "getplayerstatus error $id\n";
		exit(1);
	}

	$xml = new SimpleXMLElement($playerstatus);
	$status = $xml['status'];
	if($status != 'ok')
	{
		echo "status $status\n";
		echo "error : ".$xml->error->code."\n";
		exit(1);
	}

	$title         = (string)$xml->stream->title;
	$description   = (string)$xml->stream->description;
	$provider_type = (string)$xml->stream->provider_type;
	$co            = (string)$xml->stream->default_community;
	$watch_count   = (int)$xml->stream->watch_count;
	$comment_count = (int)$xml->stream->comment_count;
	$base_time     = (int)$xml->stream->base_time;
	$open_time     = (int)$xml->stream->open_time;
	$start_time    = (int)$xml->stream->start_time;
	$end_time      = (int)$xml->stream->end_time;
	$archive       = (int)$xml->stream->archive;

	$user_id = (int)$xml->user->user_id;

	$addr   = (string)$xml->ms->addr;
	$port   = (int)$xml->ms->port;
	$thread = (int)$xml->ms->thread;

	if(isset($argv[3]))
	{
		if($argv[3] == 's')
		{
			file_put_contents("./playerstatus/$id.xml", $playerstatus);
			exit("save $title\n");
		}
		$str = explode(':', $argv[3]);
		$cnt = count($str);
		$n = 0;
		if($cnt>=3)$watch_seek_time = $str[$n++];
		if($cnt>=2)$watch_seek_time = $watch_seek_time*60+$str[$n++];
		if($cnt>=1)$watch_seek_time = $watch_seek_time*60+$str[$n++];
	}

	echo "$co\n";
	echo "$title\n";
	echo "$description\n";
	echo "$provider_type\n";
	echo $xml->stream->owner_name."\n";
	echo $xml->user->nickname."($user_id)\n";
	echo "addr:[$addr:$port] thread:[$thread]\n";
	echo "watch_count  :".$watch_count."\n";
	echo "comment_count:".$comment_count."\n";
	echo "base_time    :".date("Y-m-d H:i:s", $base_time)."\n";
	echo "open_time    :".date("Y-m-d H:i:s", $open_time)."\n";
	echo "start_time   :".date("Y-m-d H:i:s", $start_time)."\n";
	echo "end_time     :".date("Y-m-d H:i:s", $end_time)."\n";
	echo "archive      :".$archive."\n";

	$cr = new CommentReader();
	$cr->sockopen($id, $addr, $port, $thread);

	$namedb = new Namedb($id, $co, $xml->user->user_id, $xml->user->nickname, DB_HOST, $user, $pass, DB, DB_PORT);

	$log = "";
	if($archive == 1)
	{
		$options = array('http'=>array('method'=>"GET", 'header'=>"Accept-language: ja\r\n"."Cookie: user_session=$user_session\r\n"));
		$context = stream_context_create($options);
		$url = "https://watch.live.nicovideo.jp/api/getwaybackkey?thread=$thread";
		$file = file_get_contents($url, false, $context) or die("read error $url");
		$a = explode("=", $file);
		$waybackkey = $a[1];

		$when = time();
		$buf_no = 0;
		while(1)
		{
			$res = $cr->read_buf($thread, $waybackkey, $user_id, $when);
			$data[$buf_no++] = $res;
			$when = $cr->when;
			$no0 = $cr->no0;
			$log .= "when=$when no=$no0\n";
//			file_put_contents("./tmp/$v.xml", $data[0]);
			if($no0 == 1)break;
        }
		while($buf_no > 0)
		{
			$buf = $data[--$buf_no];
			while(1)
			{
				$z = strpos($buf, "\0");
				if($z === false)break;
				$str = substr($buf, 0, $z+1);
				$buf = substr($buf, $z+1);
				$res = put_comment($str);
				if($res === true)break;
			}
		}
	}
	else
	{
		$str = "<thread res_from=\"-1\" version=\"20061206\" scores=\"1\" thread=\"$thread\" />\0";
		$cr->write($str);
		while(1)
		{
			$str = $cr->read();
			$res = put_comment($str);
			if($res === true)break;
		}
	}

	$cr->close();
?>
