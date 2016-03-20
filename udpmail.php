<?php
require __DIR__."/vendor/autoload.php";
daemonize();
define("MAIL_UDP_PORT", 1234);

$udp_worker			= new Workerman\Worker("udp://0.0.0.0:".MAIL_UDP_PORT);
$udp_worker->count	= 2;

//$client = new Predis\Client();
$mail = new PHPMailer();
$mail->IsSMTP();
$mail->Host = 'smtp.163.com';
//$mail->SMTPDebug = 2;
$mail->SMTPAuth = true;
$mail->Username = 'luluyrt'; //这里填写你的邮箱地址
$mail->Password = 'XXXXXXX'; //这里填写你的邮箱密码，
$mail->CharSet = 'utf-8';
$mail->Encoding = 'base64';

$from ='luluyrt@163.com';
//设置收信人地址
$mail->SetFrom($from,   '正常推送中心');
//设置发信人地址
$mail->AddAddress('luluyrt@163.com');
$mail->AddAddress('ritoyan66@gmail.com');
//邮件主题
$mail->Subject  = '正常推送的博客更新列表';
$mail->IsHTML(true);

$udp_worker->onMessage = function($connection, $data) use ($mail)
{
	$arr	= json_decode($data, true);
	switch($arr['type'])
	{
		//发送邮件
		case '1':
		{
			$mailaddrs	= $arr['mailaddrs'];
			if(!empty($mailaddrs) && $arr['mailbody'])
			{
				foreach($mailaddrs as $to)
				{
					$mail->clearAddresses();
					$mail->AddAddress($to);
					$mail->Body	= $arr['mailbody'];
					if(!$mail->Send())
					{
						echo "发送邮件失败：\n"."address：".$to."\n";
					}
				}
			}
			break;
		}
		default:
			break;
	}
};

Workerman\Worker::runAll();
