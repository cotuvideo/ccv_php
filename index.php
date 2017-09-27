<?php

require "./login.php";

if(isset($_COOKIE['user_session']))
{
	$user_session = $_COOKIE['user_session'];
}
else
{
	$user_session = "";
}

if(isset($_POST['cmd']))
{
	$cmd = $_POST['cmd'];
	switch($cmd)
	{
	case 'login':
		if(isset($_POST['mail']) && isset($_POST['passwd']))
		{
			$user_session = login($_POST['mail'], $_POST['passwd']);
		}
		break;
	case 'logout':
		setcookie('user_session', '', time()-1);
		$user_session = "";
		break;
	default:
		echo "$cmd<br>\n";
		break;
	}
}

echo "<!DOCTYPE html>\n";
echo "<html lang=\"ja\">\n";
echo "<head>\n";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">\n";
echo "<title>コメントさん☆彡</title>\n";
echo "</head>\n";
echo "<body>\n";

//echo "<pre>\n";
//var_dump($_COOKIE);
//echo "</pre>\n";
echo "コメントさん☆彡<br>\n";

echo "<hr>\n";

$nickname = "";
$userid = 0;
if($user_session !== "")
{
	$url = "http://www.nicovideo.jp/my/top";
	$option = array('http'=>array("method"=>"GET", "header"=>"Content-Type: application/x-www-form-urlencoded\r\nAccept-language: ja\r\nCookie: user_session=$user_session\r\n"));
	$context = stream_context_create($option);
	$file = file_get_contents($url, false, $context);

	preg_match("/nickname\s=\s\"(.*)\"/i", $file, $res);
	if(count($res) >= 2)
	{
		$nickname = $res[1];
	}

	preg_match("/data-nico-userId=\"(\d*)\"/i", $file, $res);
	if(count($res) >= 2)
	{
		$userid = $res[1];
	}

	if($nickname === "" || $userid === 0)
	{
		setcookie('user_session', '', time()-1);
	}
}
if($nickname === "" || $userid === 0)
{
	echo "<form method=\"POST\" action=\".\">\n";
	echo "<input type=\"hidden\" name=\"cmd\" value=\"login\">\n";
	echo "メールアドレス<br>\n";
	echo "<input type=\"text\" name=\"mail\"><br>\n";
	echo "パスワード<br>\n";
	echo "<input type=\"password\" name=\"passwd\"><br>\n";
	echo "<input type=\"submit\" value=\"ログイン\"><br>\n";
	echo "</form>\n";
}
else
{
	echo $nickname."さん($userid)<br>\n";

	echo "<form method=\"POST\" action=\".\">\n";
	echo "<input type=\"hidden\" name=\"cmd\" value=\"logout\">\n";
	echo "<input type=\"submit\" value=\"ログアウト\"><br>\n";
	echo "</form>\n";
}

echo "<hr>\n";

echo "<a href=\"/ccv\">top</a>\n";


echo "</body>\n";
echo "</html>\n";
?>
