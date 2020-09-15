<?php

class reader
{
	public $fp;
	public $buf = "";
	public function read()
	{
		for(;;)
		{
			$z = strpos($this->buf, "\0");
			if($z !== false)
			{
				$str = substr($this->buf, 0, $z+1);
				$this->buf = substr($this->buf, $z+1);
				return $str;
			}
			$this->buf .= fread($this->fp, 4096);
		}
	}
}

class CommentReader
{
	private $fp = false;
	private $fh = false;
	private $reader;

	public $when;
	public $no0;
	private $replay = false;

	function __construct($filename=false)
	{
		$this->reader = new reader();
		if($filename !== false)
		{
			$this->fp = fopen('lv/lv328059189.xml', 'r');
			$this->reader->fp = $this->fp;
			$this->replay = true;
		}
	}

	function sockopen($lv, $addr, $port, $thread)
	{
		if($this->replay)return;

		$this->fp = fsockopen($addr, $port, $errno, $errstr, 1);
		if($this->fp === false)
		{
			echo "error $errno:$errstr\n";
			echo "$addr:$port\n";
			exit(1);
		}
		stream_set_timeout($this->fp, 1);
		$this->reader->fp = $this->fp;

		if(SAVE)
		{
			$this->fh = fopen("lv/$lv.xml", 'w');
		}
	}

	function read()
	{
		$str = $this->reader->read();
		if($this->fh)
		{
			fwrite($this->fh, $str);
		}
		return $str;
	}

	function write($str)
	{
		fwrite($this->fp, $str);
	}

	function read_buf($thread, $waybackkey, $user_id, $when)
	{
		$res_from = 1000;
		$content = "<thread thread=\"$thread\" waybackkey=\"$waybackkey\" user_id=\"$user_id\" version=\"20061206\" res_from=\"-$res_from\" scores=\"1\" when=\"$when\" />\0";
		fwrite($this->fp, $content);
		$res = "";
		while(1)
		{
			$res .= fread($this->fp, 4096);
			$info = stream_get_meta_data($this->fp);
			if(substr($res, -1) == "\0")
			{
			//	echo sprintf("read:%4d timed_out:%d\n", strlen($res), (int)$info['timed_out']);
				$xml = simplexml_load_string("<?xml version='1.0'?><root>".str_replace("\0", "\n", $res)."</root>");
				if($xml === false)
				{
					exit("**** xml error ****\n");
				}
				$last_res = (int)$xml->thread['last_res'];
				$no0 = (int)$xml->chat[0]['no'];
				$count = (int)$xml->chat->count();
				if($last_res === (int)$xml->chat[$count-1]['no'])
				{
					break;
				}
				if($info['timed_out'])
				{
					$res .= '<chat thread="1621188173" no="2290" vpos="275600" date="1518089305" date_usec="847406" user_id="1" premium="1">**** timed_out ****</chat>'."0";
					echo "**** timed_out ****\n";
					break;
				}
			}
			if($info['timed_out'])
			{
				exit("**** timed_out ****\n");
			}
		}
		$this->when = (int)$xml->chat[0]['date'];
		$this->no0 = $no0;
		return $res;
	}

	function close()
	{
		if($this->fh)
		{
			fclose($this->fh);
			$this->fh = false;
		}
		if($this->fp)
		{
			fclose($this->fp);
			$this->fp = false;
		}
	}
}

?>
