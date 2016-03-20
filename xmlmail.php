<?php
require __DIR__.'/vendor/autoload.php';

define("SENDED_SET_KEY", "sended_set");
define("XML_URL", "http://www.ruanyifeng.com/blog/atom.xml");
define("RECENT_NUM", 5);
define("GAP_SECONDS", 600);
define("EMAIL_LIST_KEY", "email_list");
define("EVERY_SEND_NUM", 1);

$client	= new Predis\Client();

$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
daemonize();
while(1)
{
	//获取最新的几篇文章，看看是否需要推送
	$c = file_get_contents(XML_URL);
	$parse = @simplexml_load_string($c);
	if($parse)
	{
		$count  = count($parse->entry);
		$count  = $count > RECENT_NUM ? RECENT_NUM : $count;
		$maynew = []; 
		for($i = 0; $i < $count; $i++)
		{
			$maynew[$parse->entry[$i]->link->attributes()->href->__toString()]   = $parse->entry[$i]->title->__toString();
		}
		
		$body 	= "";

		//是否推送
		foreach($maynew as $url => $title)
		{
			if($client->sadd(SENDED_SET_KEY, $url))
			{
				//send EMAIL
				$body	.= "<a href='".$url."'>".$title."</a><br>";
			}
		}
		if($body)
		{
			$msg				= [];
			$msg['type']		= 1;
			$msg['mailbody']	= $body;
			$start				= 0;
			while($mailaddrs = $client->lrange(EMAIL_LIST_KEY ,$start, ($start + EVERY_SEND_NUM -1 )))
			{
				$msg['mailaddrs']	= $mailaddrs;
				$send_msg			= json_encode($msg);
				socket_sendto($sock, $send_msg, strlen($send_msg), 0, '127.0.0.1', 1234);
				$start				+= EVERY_SEND_NUM;
			}
		}
	}
	sleep(GAP_SECONDS);
}

socket_close($sock);
