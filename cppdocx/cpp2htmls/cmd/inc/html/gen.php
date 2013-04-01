<?php
	$env = array('base' => '', 'nsdisp' => '');
	
	// array for global function
	$gFunction = array();
	// array for class function
	$cFunction = array();
	// array for .summary file
	$summary = array();
	$summary['base'] = getcwd();	
	
	foreach ($doc->sentences as $s)
	{
		if (isset($s->comment))
		{
			$comment = $s->comment;
			if (isset($comment->category))
				$env['category'] = $comment->category;
			if (isset($comment->ns))
			{
				$base = str_replace(array('::', '.', '\\'), array('/', '/', '/'), $comment->ns) . '/';
				$namespace = $comment->ns;
				$base = str_replace('//', '/', $base);
				@mkdir($base, 0755, true);
				$env['base'] = $base;
				$env['nsdisp'] = str_replace('/', '::', $base);
			}
		}
		else
		{
			if (!isset($base))
				$base = './';
			if (isset($s->macro))
				show_macro($comment, $s, $env);
			if (isset($s->class))
				show_class($comment, $s, $env);
			else if (isset($s->global))
				show_global_fn($comment, $s, $env);
			unset($comment);
		}
	}
	
	// clear old htm for globle function
	$count = count($gFunction);
	for ($i = 0; $i < $count; $i = $i + 3)
	{
		$gf = $gFunction[$i];
		$file = $env['base'] . $gf . '.htm';
		if (!file_exists($file))
		{
			$fp = fopen($file, 'w');
			$title = $namespace . '::' . $gf;
			html_header($fp, $title, $file, $env);
			$des = '<H1>' . $title . '</H1><H4>该函数的重载函数如下：</H4><TABLE height=\"1\"><TR VALIGN=\"top\">';
			$des = $des . '<TH align="left" width="35%" height="19">Method</TH><TH align="left" width="65%" height="19">Description</TH></TR>';
			fwrite($fp, $des);		
			fclose($fp);
		}
	}
	
	// generate globle function browse htm
	for ($i = 0; $i < $count; $i = $i + 3)
	{
		$gf = $gFunction[$i];
		$fn = $gFunction[$i + 1];
		$des = $gFunction[$i + 2];
		$file = $env['base'] . $gf . '.htm';
		$fp = fopen($file, 'a+');
		$show = '<TD width=\"35%\" height=\"19\">'. $fn . '</TD><TD width=\"65%\" height=\"19\">' . $des . '</TD></TR>';
		fwrite($fp, $show);
		fclose($fp);
	}

	// generate .summary file
	$fp = fopen($cpp_path . "/" . $cpp_file . ".summary", 'w');
	fwrite($fp, json_encode($summary));
	fclose($fp);
?>
