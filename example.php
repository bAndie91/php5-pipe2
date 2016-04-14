<?php

function execve($cmd, $args = array(), $redir = array(), $input_data = array())
{
	/*
		$cmd: name or path to command to run,
			eg. "uname", "./a.out", "/bin/ls"
		$args[]: command's arguments,
			eg. array("-r", "-v")
		$redir[]: setup redirections from/to file,
			eg. array('stdin'=>'/dev/null', stderr=>'/tmp/log')
		$input_data[]: string data for reader pipes,
			eg. array('stdin'=>'this will be piped into the command'.PHP_EOL)
		returns: array(
			'stdout'=>'output text', // not exists if stdout is redirected
			'stderr'=>'error text', // not exists if stderr is redirected
			'code'=>0) // the exit code
	*/
	
	$fdnames = array(0=>'stdin', 1=>'stdout', 2=>'stderr');
	$direction = array(
		'parent' => array(0=>'writer', 1=>'reader', 2=>'reader'),
		'child'  => array(0=>'reader', 1=>'writer', 2=>'writer'),
	);
	$return = array();
	if(preg_match('/^\.{0,2}[\/]/', $cmd))
	{
		$executable = $cmd;
	}
	else
	{
		$executable = which($cmd);
		if($executable === false)
		{
			throw new Exception("command not found: $cmd (PATH=".getenv('PATH').")");
		}
	}

	/* Spawn process */
	$inner_pipes = array();
	foreach($fdnames as $fd => $fdname)
	{
		if(isset($redir[$fdname]))
		{
			$open_end = $direction['child'][$fd];
			$close_end = $direction['parent'][$fd];
			$inner_pipes[$fd][$open_end]['stream'] = fopen($redir[$fdname], substr($open_end, 0, 1));
			if($inner_pipes[$fd][$open_end]['stream'] === FALSE)
			{
				throw new Exception("fopen({$redir[$fdname]}) failed");
			}
			$inner_pipes[$fd][$close_end] = NULL;
		}
		else
		{
			$pipe = posix_pipe();
			if(!is_array($pipe))
			{
				throw new Exception("pipe() failed");
			}
			$inner_pipes[$fd] = array(
				'reader' => array('stream' => $pipe[0], /* 'fd' => $pipe[2], */),
				'writer' => array('stream' => $pipe[1], /* 'fd' => $pipe[3], */),
			);
		}
	}
		
	$pid = pcntl_fork();
	if($pid == -1)
	{
		throw new Exception("fork() failed");
	}
	elseif($pid == 0)
	{
		/* Child process */
		foreach($inner_pipes as $fd => $pipe)
		{
			$open_end = $direction['child'][$fd];
			$close_end = $direction['parent'][$fd];

			/* Close parent's FD */
			if(isset($pipe[$close_end]['stream']))
			{
				fclose($pipe[$close_end]['stream']);
			}
			/* Close standard FD to be able to reopen it */
			posix_close($fd);
			/* Duplicate original FD to standard FD */
			posix_dup2($pipe[$open_end]['stream'], $fd);
			/* Close original FD */
			fclose($pipe[$open_end]['stream']);
		}
		/* Close all other FDs */
		for($fd = 3; $fd <= 255; $fd++)
		{
			posix_close($fd);
		}
		posix_setsid();
		pcntl_exec($executable, $args);
		exit(127);
	}
	else
	{
		/* Parent process */
		foreach($inner_pipes as $fd => $pipe)
		{
			$open_end = $direction['parent'][$fd];
			$close_end = $direction['child'][$fd];
			fclose($pipe[$close_end]['stream']);
			$pipes[$fd] = $pipe[$open_end]['stream'];
		}
	}

	/* Write input */
	foreach($pipes as $fd => $stream)
	{
		if($direction['parent'][$fd] == 'writer' and $stream !== NULL)
		{
			if(isset($input_data[$fdnames[$fd]]))
			{
				fwrite($stream, $input_data[$fdnames[$fd]]);
			}
			fclose($stream);
		}
	}
	
	/* Read output */
	foreach($pipes as $fd => $stream)
	{
		// TODO: stream_select
		if($direction['parent'][$fd] == 'reader' and $stream !== NULL)
		{
			$return[$fdnames[$fd]] = stream_get_contents($stream);
			fclose($stream);
		}
	}

	/* Close process */	
	pcntl_waitpid($pid, $proc_status);
	$return['code'] = pcntl_wexitstatus($proc_status);
	return $return;
}

function which($name)
{
	foreach(explode(':', getenv('PATH')) as $path)
	{
		$path = preg_replace('/\/*$/', '', $path);
		if(!is_dir("$path/$name") and is_executable("$path/$name"))
		{
			return "$path/$name";
		}
	}
	return false;
}
