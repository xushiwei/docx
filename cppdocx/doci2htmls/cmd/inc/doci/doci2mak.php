<?php
	// php doci2mak.php <makefile> <command> <dociPath>

	$all = "all = ";
	$pathSet = "\n";
	$makeFile = $argv[1];
	$command = $argv[2];
	$dociPath = $argv[3];
	
	function genMakefileItem($item)
	{
		global $all, $pathSet;
		global $dociPath, $command;
		$srcFile = "$dociPath/$item->path";
		$destFile = "$srcFile.summary";
		$all .= $destFile . ' ';
		$pathSet .= "\n$destFile : $srcFile\n";
		$pathSet .= "\t$command $srcFile\n";
	}
	
	function processItems($items)
	{
		$count = count($items);
		for ($i = 0; $i < $count; $i++)
		{
			if (isset($items[$i]->item->items))
				processItems($items[$i]->item->items);
			else
				genMakefileItem($items[$i]->item);
		}
	}

	$doc = json_decode(stream_get_contents(STDIN));

	processItems($doc->items);
	$fp = fopen($makeFile, 'w');
	fwrite($fp, $all);
	$summary = "\n\nobject : $(all)\n\t summary $(all)";
	fwrite($fp, $summary);
	fwrite($fp, $pathSet);
	fclose($fp);
?>
