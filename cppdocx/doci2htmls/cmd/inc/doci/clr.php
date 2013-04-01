<?php
	function deldir($dir)
	{
		$dh = opendir($dir);
		while ($file = readdir($dh)) 
		{
			if ($file != "." && $file != "..")
			{
				$fullpath=$dir . "/" . $file;
				if (!is_dir($fullpath))
					unlink($fullpath);
				else
					deldir($fullpath);
			}
		}
		closedir($dh);
		if(rmdir($dir)) 
			return true;
		else 
			return false;
	}
	
	$env = array('base' => './', 'nsdisp' => '');
	
	//array for global function
	$gFunction = array();
	//array for class function
	$cFunction = array();
	
	foreach ($doc->sentences as $s)
	{
		if (isset($s->comment))
		{
			$comment = $s->comment;
			if (isset($comment->category))
				$env['category'] = $comment->category;
			if (isset($comment->ns))
				if (is_dir($comment->ns))
					deldir($comment->ns);
		}
	}
?>
