<?php

$pipes = posix_pipe();
var_dump($pipes);
list($rdr, $wtr, $fd0, $fd1) = $pipes;

$pid = pcntl_fork();
if($pid==0)
{
	fclose($rdr);
	fwrite($wtr, "fwrite wtr\n");
	
	//ob_end_clean();
	//fclose(STDOUT);
	//$stdout = fopen("php://fd/$fd1", "w");
	
	//$ok = stream_dup2($wtr, STDOUT);
	$ok = posix_dup2($fd1, 1);
	//var_dump($ok);

	echo "echo\n";
	
	fwrite(STDOUT, "fwrite STDOUT\n");
	file_put_contents("/dev/stdout", "/dev/stdout\n");
	//file_put_contents("/proc/self/fd/1", "/dev/stdout\n");
	
	//foreach(glob("/proc/self/fd/*") as $f) var_dump(array($f,readlink($f)));
	foreach(glob("/dev/std*") as $f) var_dump(array($f,readlink($f)));
	
	die;
}
else
{
	fclose($wtr);

	while(!feof($rdr)) @$buf .= fread($rdr, 4096);
	var_dump( $buf );
}
;
