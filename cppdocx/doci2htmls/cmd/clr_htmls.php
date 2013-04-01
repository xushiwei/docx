<?php
	$argc = $_SERVER["argc"];
	$exec_path = dirname($argv[0]);
	
	if ($argc == 1)
		$doc = json_decode(stream_get_contents(STDIN));
	else
		$doc = json_decode(file_get_contents($argv[1]));
	
	if ($doc)
	{
		require($exec_path . "/inc/doci/clr.php");
		return 0;
	}
	else
	{
		echo " >>> ERROR: Input file maybe isn't a valid json file?\n\n";
		return -2;
	}
?>
