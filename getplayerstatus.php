<?php

function getplayerstatus($v, $user_session)
{
	$options = array('http'=>array('method'=>"GET", 'header'=>"Accept-language: ja\r\n"."Cookie: user_session=$user_session\r\n"));
	$context = stream_context_create($options);
	$url = "http://live.nicovideo.jp/api/getplayerstatus?v=$v";
	return file_get_contents($url, false, $context);
}

?>
