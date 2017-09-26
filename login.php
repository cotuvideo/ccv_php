<?php
function login($mail, $password)
{
	$url = "https://secure.nicovideo.jp/secure/login?site=niconico";
	$data = http_build_query(array('next_url'=>"", 'mail'=>$mail, 'password'=>$password, 'submit'=>""));
	$option = array('http'=>array('method'=>"POST", 'header'=>"Content-Type: application/x-www-form-urlencoded\r\nAccept-language: ja\r\n", 'content'=>$data));
	$context = stream_context_create($option);
	$file = file_get_contents($url, false, $context);

	foreach($http_response_header as $res)
	{
		preg_match("(user_session=user_session_[0-9a-z_]+)", $res, $tmp);
		if(count($tmp) > 0)
		{
			$user_session = $tmp[0];
			setcookie('user_session', $user_session);
		}
	}
	return $user_session;
}
?>
