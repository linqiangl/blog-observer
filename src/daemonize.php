<?php
function daemonize()
{
	umask(0);
	$pid = pcntl_fork();
	if(-1 === $pid)
	{
		throw new Exception('fork fail');
	}
	elseif($pid > 0)
	{
		exit(0);
	}
	if(-1 === posix_setsid())
	{
		throw new Exception("setsid fail");
	}
	// Fork again avoid SVR4 system regain the control of terminal.
	$pid = pcntl_fork();
	if(-1 === $pid)
	{
		throw new Exception("fork fail");
	}
	elseif(0 !== $pid)
	{
		exit(0);
	}
}
