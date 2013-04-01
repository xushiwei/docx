<?php
	$argc = $_SERVER["argc"];
	$argv = $_SERVER["argv"];
	$AppPath = dirname($argv[0]);

	function genHtml($item)
	{
		system("cpp2htmls $item->path");
		//system("cpp2json $item->path | php $AppPath/gen_htmls");
	}
	
	function processItems($items)
	{
		$count = count($items);		
		for ($i = 0; $i < $count; $i++)
		{
			if (isset($items[$i]->item->items))
				processItems($items[$i]->item->items);
			else
				genHtml($items[$i]->item);
		}
	}
	
	function clear($item)
	{
		global $AppPath;
		//system("cpp2json $item->path | php $AppPath/clr_htmls.php");
		system("cpp2json $item->path > $AppPath/temp.json");
		system("php $AppPath/clr_htmls.php $AppPath/temp.json");
	}
	
	function clearHtmls($items)
	{
		$count = count($items);		
		for ($i = 0; $i < $count; $i++)
		{
			if (isset($items[$i]->item->items))
				clearHtmls($items[$i]->item->items);
			else
				clear($items[$i]->item);
		}
	}
	
	if ($argc == 1)
		$doc = json_decode(stream_get_contents(STDIN));
	else
		$doc = json_decode(file_get_contents($argv[1]));

	if ($doc)
	{
		//清除上一次生成的工程文件，主要内容包括：
		//删除namespace的文件夹以及内部的所有文件
		//system("clearHtmls $item->path");
		
		clearHtmls($doc->items);
		processItems($doc->items);
		unlink("$AppPath/temp.json");
	}
	else
		echo " >>> ERROR: Input file maybe isn't a valid json file?\n\n";
?>
