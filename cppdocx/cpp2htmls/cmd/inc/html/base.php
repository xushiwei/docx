<?php

// -------------------------------------------------------------------------
// tag or var sets
$trans_newline = array(
	"\\p" . PHP_EOL => "<br>",
	"\\n" . PHP_EOL => "<br>",
);

$html_tags = array(
	"a" 	=> true, "b" 	=> true, "i" 	=> true, "u" 	=> true,
	"img" 	=> true, "p" 	=> true, "br" 	=> true, "pre" 	=> true,
	"code" 	=> true, "h1" 	=> true, "h2" 	=> true, "h3" 	=> true,
	"h4" 	=> true, "li" 	=> true, "ul" 	=> true, "ol" 	=> true,
	"font" 	=> true, "table" => true, "tr" 	=> true, "th" 	=> true,
	"td" 	=> true, "em" 	=> true
);


// -------------------------------------------------------------------------
// utilities
function encode_angle_bracket($text)
{
	global $html_tags;
	$off = 0;
	$str = "";
	$len = strlen($text);
	$tag_states = array();

	for ($i = 0; $i < $len; $i++)
	{
		$cur = $text[$i];
		if ($cur ==  '<')
		{
			$j = $i + 1;
			while($text[$j] == ' ') $j ++;
			for ($j; $j < $len; $j ++)
			{
				$ch = $text[$j];
				if ($ch == ' ' || $ch == '>')
					break;
			}
			$tlen = $j - $i - 1;
			$tagname = substr($text, $i+1, $tlen);
			$key = ($tagname[0] == '/') ? strtolower(substr($tagname, 1)) : strtolower($tagname);
			if (@$html_tags[$key])
			{
				array_push($tag_states, "yes");
				$str .= "<$tagname";
			}
			else
			{
				array_push($tag_states, "no");
				$str .= "&lt;$tagname";
			}
			$i += $tlen;
		} // end of "if ($cur ==  '<')"
		else if ($cur == '>')
		{
			$val = array_pop($tag_states);
			if ($val == "yes")
				$str .= '>';
			else if (NULL == $val)
				$str .= '>';
			else 
				$str .= '&gt;';
		} // end of "else if ($cur == '>')"
		else
		{
			$str .= $cur;
		}
	}
	return $str;
}

function match_reference($text, $file, $env)
{
	$off = 0;
	$str = "";
	$total_len = strlen($text);
	
	for (;;)
	{
		$pos = strpos($text, '\\<', $off);
		if ($pos === false)
		{
			$str .= substr($text, $off);
			break;
		}
		$len  = $pos - $off;
		$str .= substr($text, $off, $len); 
		$off = $pos + 2;
		
		$pre_flag = false;
		for ($i = $off; $i < $total_len; $i++)
		{
			$ch = $text[$i];
			if ( $ch == '<' )
			{
				$pre_flag = true;
				break;
			}
			else if ($ch == '>')
			{
				break;
			}
		}
		$key = substr($text, $off, $i - $off);
		if ($pre_flag)
		{
			$str .= "\<" . $key;
			$off = $i;
		}
		else
		{
			$off = $i + 1;		
			$href = str_replace('::', '/', $key) . '.htm';
			$pos = strpos($href, '/');
			if ($pos === false)
				$href = $env['base'] . $href;
			else if ($pos === 0)
				$href = substr($href, 1);
			$href = makerel($href, $file);
			$str .= "<a href=\"$href\"><b>$key</b></a>";
		}
	}
	return $str;
}

function esctext2html($text, $file, $env)
{
	global $html_tags;
	global $trans_newline;
	$text = strtr($text, $trans_newline);
	$text = match_reference($text, $file, $env);
	return encode_angle_bracket($text);
}

function pathspec($name)
{
	return str_replace(array('::', '*', '/', '?'), array('_', 'MUL', 'DIV', '$'), $name);
}

function makerel($href, $file)
{
	$off = 0;
	for (;;)
	{
		$pos = strpos($href, '/', $off);
		if ($pos === false)
			break;
		$len = $pos + 1 - $off;
		if (substr($href, $off, $len) != substr($file, $off, $len))
			break;
		$off = $pos + 1;
	}
	
	$p = '';
	$href = substr($href, $off);
	for (;;)
	{
		$pos = strpos($file, '/', $off);
		if ($pos === false)
			return $p . $href;
		$p .= '../';
		$off = $pos + 1;
	}
}

function extended_text($fp, $rtf, $file, $env)
{
	foreach ($rtf as $item)
	{
		if (isset($item->text))
		{
			$text = esctext2html($item->text, $file, $env);
			fwrite($fp, "$text");
		}
		else if (isset($item->table))
		{
			fwrite($fp, 
"<TABLE height=\"1\"><TR VALIGN=\"top\">
<TH align=\"left\" width=\"35%\" height=\"19\">Value</TH>
<TH align=\"left\" width=\"65%\" height=\"19\">Meaning</TH>
</TR>\n");
			foreach ($item->table->vals as $v)
			{
				$text = esctext2html($v->text, $file, $env);
				fwrite($fp,
"<TR VALIGN=\"top\"><TD width=\"35%\" height=\"19\">$v->name</TD>
<TD width=\"65%\" height=\"19\">$text</TD></TR>\n");
			}
			fwrite($fp, "</TABLE>\n");
		}
	}
}

function show_args($fp, $comment, $file, $env)
{
	if (!isset($comment))
		return;
	
	if (!isset($comment->args))
		return;
	
	fwrite($fp, "<H4>参数说明</H4><DL>");
	foreach ($comment->args as $arg)
	{
		fwrite($fp, "<DT>");
		if (isset($arg->attr))
			fwrite($fp, "[$arg->attr] ");
		fwrite($fp, "<I>$arg->name</I></DT><DD>");
		extended_text($fp, $arg->body, $file, $env);
		fwrite($fp, "</DD>");
	}
	fwrite($fp, "</DL>\n");
}

function show_sees($fp, $sees, $file, $env)
{
	if (!isset($sees))
		return;
	
	$notfirst = false;
	fwrite($fp, "<H4>See Also</H4><DL><DT><B>Reference</B></DT><DD>");
	foreach ($sees as $see)
	{
		foreach ($see->topics as $topic)
		{
			$href = str_replace('::', '/', $topic->name) . '.htm';
			$pos = strpos($href, '/');
			if ($pos === false)
				$href = $env['base'] . $href;
			else if ($pos === 0)
				$href = substr($href, 1);
			$href = makerel($href, $file);
			$text = (isset($topic->text) ? $topic->text : $topic->name);
			if ($notfirst)
				fwrite($fp, ' | ');
			else
				$notfirst = true;
			fwrite($fp, "<a href=$href><b>$text</b></a>");
		}
	}
	fwrite($fp, "</DD></DL>\n");
}

// -------------------------------------------------------------------------
// topic start/end

function html_header($fp, $title, $file, $env)
{
	$respath = makerel('res', $file);
	fwrite($fp,
"<HEAD>
<META http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
<TITLE>$title</TITLE>
<SCRIPT SRC=\"$respath/langref.js\"></SCRIPT>
<LINK REL=\"STYLESHEET\" HREF=\"$respath/backsdk4.css\">
</HEAD>
");
}

function header_bar($fp, $category)
{
	fwrite($fp,
"<TABLE CLASS=\"buttonbarshade\" CELLSPACING=\"0\">
<TR><TD>&#x20;</TD></TR>
</TABLE>
<TABLE CLASS=\"buttonbartable\" CELLSPACING=\"0\">
<TR ID=\"hdr\">
<TD CLASS=\"runninghead\" NOWRAP=\"NOWRAP\">$category</TD>
</TR>
</TABLE>
");
}

function show_brief($fp, $comment, $file, $env)
{
	if (!isset($comment))
		return;

	if (!isset($comment->brief))
	{
		if (isset($comment->summary))
			fwrite($fp, $comment->summary);
		return;
	}
	extended_text($fp, $comment->brief, $file, $env);
}

function show_desc($fp, $comment)
{
}

function show_retval($fp, $comment, $file, $env)
{
	if (!isset($comment) || !isset($comment->return))
		return;
	
	fwrite($fp, "<H4>返回值</H4>\n");
	extended_text($fp, $comment->return, $file, $env);
}

function show_remark($fp, $comment, $file, $env)
{
	if (!isset($comment) || !isset($comment->remark))
		return;
	
	fwrite($fp, "<H4>注意点</H4>\n");
	extended_text($fp, $comment->remark, $file, $env);
}

function topic_start($fp, $comment, $rel, $file, $env)
{
	fwrite($fp, "<HTML>");
	$category = @$env['category'];
	$header = $env['nsdisp'] . $rel;
	$title = isset($category) ? "$header - $category" : $header;
	html_header($fp, $title, $file, $env);
	fwrite($fp, "<BODY TOPMARGIN=\"0\">\n<!--%MENU%-->\n");
	if (isset($category))
		header_bar($fp, $category);
	fwrite($fp, "<H1>${header}</H1>");
	show_brief($fp, $comment, $file, $env);
}

function topic_end($fp, $comment, $file, $env)
{
	$respath = makerel('res', $file);
	fwrite($fp, "<DIV CLASS=\"itfBorder\"><IMG SRC=\"$respath/tiny.gif\" width=\"1\" height=\"1\"/></DIV>\n");
	show_sees($fp, @$comment->sees, $file, $env);
	fwrite($fp, "<!--%COMMENT%-->\n");
	fwrite($fp, "</BODY></HTML>");
}

// -------------------------------------------------------------------------
// show fn

function check_topic_name($comment, $code)
{
	if (!isset($code->name) || !isset($comment) || !isset($comment->topic) )
		return true;
	
	foreach ($comment->topic->args as $arg)
	{
		if ($arg == $code->name)
			return true;
	}
	return false;
}

function check_arg_names($comment, $code)
{
	if (!isset($comment) || !isset($comment->args) )
		return true;

	if (!isset($code->args))
		return false;

	$args1 = $comment->args;
	$args2 = $code->args;
	
	$count = count($args1);
	if ($count != count($args2))
		return false;
	for ($i = 0; $i < $count; ++$i)
	{
		if ($args1[$i]->name != $args2[$i]->name)
			return false;
	}
	return true;
}

function fn_decl($fp, $template, $fn)
{
	fwrite($fp, "<PRE class=\"syntax\"><B>");
	if (isset($template))
		fwrite($fp, htmlspecialchars(trim($template->header)) . "<BR>");
	if (isset($fn->type))
		fwrite($fp, "$fn->type");
	if (isset($fn->calltype))
		fwrite($fp, "$fn->calltype ");
	fwrite($fp, "$fn->name(</B>");
	if (isset($fn->args))
	{
		foreach ($fn->args as $i => $arg)
		{
			if ($i != 0)
				fwrite($fp, ',');
			fwrite($fp, "<BR><B>&#x20;&#x20;$arg->type</B><I>$arg->name</I>");
			if (isset($arg->defval))
				fwrite($fp, " = $arg->defval");
		}
		fwrite($fp, "<BR>");
	}
	$fnattr = ($fn->funcattr ? " $fn->funcattr" : "");
	fwrite($fp, "<B>)$fnattr;<BR></B></PRE>\n");
}

function macro_decl($fp, $macro)
{
	fwrite($fp, "<PRE class=\"syntax\"><B>#define $macro</B></PRE>\n");
}

function insertSummary($type, $name, $path, $ns, $brief, $pos)
{
	global $summary;
	if ($type == "paths")
	{
		$summary["paths"][] = $path . $name;
		$summary["positions"][] = $pos;
	}
	else if ($type == "globals")
	{
		$item = array();
		$item["ns"] = $ns;
		$item["filename"] = $name;
		$item["summary"] = $brief;
		$summary["globals"][] = $item;
	}
}

function show_fn($comment, $s, $rel, $env)
{
	global $cpp_path, $cpp_file;
	$fntype = $env['fntype'];
	$fn = $s->$fntype;
	$file = $env['base'] . $rel . '.htm';
	$fp = fopen($file, 'w');
	if (!$fp) {
		echo "---> ERROR: Create file `$file` failed!\n";
		return;
	}

	topic_start($fp, $comment, $rel, $file, $env);
	fn_decl($fp, @$s->template, $fn);
	show_args($fp, $comment, $file, $env);
	show_retval($fp, $comment, $file, $env);
	show_desc($fp, $comment);
	show_remark($fp, $comment, $file, $env);
	topic_end($fp, $comment, $file, $env);
	
	$path = $env['base'];
	$name = "$rel.htm";
	insertSummary("paths", $name, $path, "", "", mb_strpos(str_replace("\t", "    ", file_get_contents($cpp_path . "/" . $cpp_file)), $rel . "(", 0, "UTF-8"));
	
	fclose($fp);
}

function goverloaded($sentences, $function)
{
	$count = 0;
	foreach ($sentences as $s)
	{
		if (isset($s->global))
		{
			if ($s->global->name == $function)
				$count++;
		}
	}
	if ($count == 1)
		return false;
	else
		return true;
}

function show_global_fn($comment, $s, $env)
{
	global $gFunction, $doc, $namespace, $summary, $cpp_path, $cpp_file;
	$global = $s->global;
	$filename = $global->name . '(';
	$count = 0;
	if (isset($global->args))
		foreach ($global->args as $arg)
	{
		if ($count > 0)
			$filename = $filename . ',';
		$count++;
		$filename = $filename . $arg->name;
	}
	
	$filename = $filename . ')';
	$file = $env['base'] . $filename . '.htm';
	$fp = fopen($file, 'w');
	if (!$fp) {
		echo "---> ERROR: Create file `$file` failed!\n";
		return;
	}
	
	topic_start($fp, $comment, $global->name, $file, $env);
	fn_decl($fp, @$s->template, $global);
	show_args($fp, $comment, $file, $env);
	show_retval($fp, $comment, $file, $env);
	show_desc($fp, $comment);
	show_remark($fp, $comment, $file, $env);
	topic_end($fp, $comment, $file, $env);
	
	$ns = substr($env['base'], 0, strpos($env['base'], '/'));
	$brief = $comment->summary . $comment->brief;
	insertSummary("paths", $filename . '.htm', $env['base'], "", "", mb_strpos(str_replace("\t", "    ", file_get_contents($cpp_path . "/" . $cpp_file)), $global->name . "(", 0, "UTF-8"));
	insertSummary("globals", $filename . '.htm', "", $ns, $brief, 0);
	
	fclose($fp);
	
	if (goverloaded($doc->sentences, $global->name))
	{
		$href = '<b><a href="'. $filename . '.htm">'. $filename . '</a></b>';
		array_push($gFunction, $global->name);
		array_push($gFunction, $href);
		array_push($gFunction, $comment->summary);
	}
}

function show_macro($comment, $sentence, $env)
{
	global $cpp_path, $cpp_file;
	if (isset($comment->topic))
	{
		$macro_topic = $comment->topic;
		$macro_name = $macro_topic->args[0];
		
		$file = $macro_name . '.htm';
		$fp = fopen($file, 'w');
		if (!$fp) {
			echo "---> ERROR: Create file `$file` failed!\n";
			return;
		}
		
		$env2 = array_merge($env, array('nsdisp' => 'macro '));
		topic_start($fp, $comment, $macro_name, $file, $env2);
		macro_decl($fp, $macro_name);
		show_args($fp, $comment, $file, $env);
		show_retval($fp, $comment, $file, $env);
		show_desc($fp, $comment);
		show_remark($fp, $comment, $file, $env);
		topic_end($fp, $comment, $file, $env);
		
		insertSummary("paths", $file, "", "", "", mb_strpos(str_replace("\t", "    ", file_get_contents($cpp_path . "/" . $cpp_file)), "#define " . $macro_name, 0, "UTF-8"));
		
		fclose($fp);
	}
	else
	{
		$macro_name = $sentence->macro->name;
		
		$args = $sentence->macro->arglist->args;
		if (isset($sentence->macro->arglist))
			$macro_args = '(';
		$count = 0;
		if (isset($args))
			foreach ($args as $arg)
		{
			if ($count++ == 0)
				$macro_args = $macro_args . $arg;
			else
				$macro_args = $macro_args . ',' . $arg;
		}
		if (isset($sentence->macro->arglist))
			$macro_args = $macro_args . ')';
		$macro_name = $macro_name . $macro_args;
		
		$file = $sentence->macro->name . '.htm';
		$fp = fopen($file, 'w');
		if (!$fp) {
			echo "---> ERROR: Create file `$file` failed!\n";
			return;
		}
		
		$env2 = array_merge($env, array('nsdisp' => 'macro '));
		topic_start($fp, $comment, $macro_name, $file, $env2);
		macro_decl($fp, $macro_name);
		show_args($fp, $comment, $file, $env);
		show_retval($fp, $comment, $file, $env);
		show_desc($fp, $comment);
		show_remark($fp, $comment, $file, $env);
		topic_end($fp, $comment, $file, $env);
		
		insertSummary("paths", $file, "", "", "", mb_strpos(str_replace("\t", "    ", file_get_contents($cpp_path . "/" . $cpp_file)), "#define " . $sentence->macro->name, 0, "UTF-8"));
		
		fclose($fp);
	}
}

// -------------------------------------------------------------------------
// show fntable

function has_overload($fns, $fntype, $name, $i, $n)
{
	if ($i >= 2 && $fns[$i - 1]->$fntype->name == $name)
		return true;
	return ($i + 2 < $n && $fns[$i+3]->$fntype->name == $name);
}

function show_index($fp, $fns, $file, $env)
{
	$count = count($fns);	
	if ($count == 0)
		return;
	
	fwrite($fp, "<H4>$env[title]</H4>
<TABLE height=\"1\">
<TR VALIGN=\"top\">
<TH align=\"left\" width=\"35%\" height=\"19\">$env[name]</TH>
<TH align=\"left\" width=\"65%\" height=\"19\">$env[desc]</TH>
</TR>");
	
	$base = $env['base'];
	$fntype = $env['fntype'];
	
	for ($i = 0; $i < $count; $i += 2)
	{
		$fn = $fns[$i+1]->$fntype;
		$args_text = "";
		if (isset($fn->args))
		{
			foreach ($fn->args as $j => $arg)
			{
				if ($j != 0)
					$args_text .= ",";
				$args_text .= $arg->name;
			}
		}
		
		$name = $fn->name;
		$overload = has_overload($fns, $fntype, $name, $i, $count);
		$rel = pathspec($name) . ($overload ? "($args_text)" : "");
		$href = makerel("$base$rel.htm", $file);
		
		fwrite($fp, "<TR VALIGN=\"top\">
<TD width=\"35%\" height=\"19\"><b><a href=$href>$name($args_text)</a></b>\n</TD>
<TD width=\"65%\" height=\"19\">\n"); 
		show_brief($fp, $fns[$i], $file, $env);
		fwrite($fp, "</TD></TR>\n");
		show_fn($fns[$i], $fns[$i+1], $rel, $env);
	}
	fwrite($fp, "</TABLE>\n");
}

function push_cFunction($member, $class, $comment)
{
	global $cFunction;
	$filename = $member->name . '(';
	$count = 0;
	if (isset($member->args))
		foreach ($member->args as $arg)
	{
		if ($count > 0)
			$filename = $filename . ',';
		$count++;
		$filename = $filename . $arg->name;
	}
	
	$filename = $filename . ')';
	$href = '<b><a href="'. $filename . '.htm">'. $filename . '</a></b>';	
	
	array_push($cFunction, $class);
	array_push($cFunction, $member->name);
	array_push($cFunction, $href);
	array_push($cFunction, $comment->summary);
}

function ctor_overloaded($sentences, $function)
{
	$count = 0;
	foreach ($sentences as $s)
	{
		if (isset($s->ctor))
		{
			if ($s->ctor->name == $function)
				$count++;
		}
	}
	if ($count == 1)
		return false;
	else
		return true;
}

function overloaded($sentences, $function)
{
	$count = 0;
	foreach ($sentences as $s)
	{
		if (isset($s->member))
		{
			if ($s->member->name == $function)
				$count++;
		}
	}
	if ($count == 1)
		return false;
	else
		return true;
}

function show_fntable($fp, $code, $file, $env)
{
	global $cFunction;
	$cFunction = array();
	$fntype = $env['fntype'];
	$cname = $code->name;
	
	if (isset($code->sentences))
		foreach ($code->sentences as $s)
	{
		if (isset($s->comment))
			$comment = $s->comment;
		else
		{
			if (isset($s->$fntype) && isset($s->$fntype->funcattr))
			{
				$fns[] = $comment;
				$fns[] = $s;
			}
			if (isset($s->member) && overloaded($code->sentences, $s->member->name))
				push_cFunction($s->member, $cname, $comment);
			if (isset($s->ctor) && ctor_overloaded($code->sentences, $s->ctor->name))
				push_cFunction($s->ctor, $cname, $comment);
			unset($comment);
		}
	}
	show_index($fp, $fns, $file, $env);
	
	$count = count($cFunction);
	for ($i = 0; $i < $count; $i = $i + 4)
	{
		$class = $cFunction[$i];
		$gf = $cFunction[$i + 1];
		$file = $env['base'] . $gf . '.htm';
		$fp = fopen($file, 'w');
		$title = $class . '::' . $gf;
		html_header($fp, $title, $file, $env);
		$des = '<H1>' . $title . '</H1><H4>该函数的重载函数如下：</H4><TABLE height=\"1\"><TR VALIGN=\"top\">';
		$des = $des . '<TD width=\"35%\" height=\"19\"><b>Method</b></TD><TD width=\"65%\" height=\"19\"><b>Description</b></TD></TR>';
		fwrite($fp, $des);
		fclose($fp);
	}
	
	for ($i = 0; $i < $count; $i = $i + 4)
	{	
		$class = $cFunction[$i];
		$gf = $cFunction[$i + 1];
		$fn = $cFunction[$i + 2];
		$des = $cFunction[$i + 3];
		$file = $env['base'] . $gf . '.htm';
		$fp = fopen($file, 'a+');
		$show = '<TD width=\"35%\" height=\"19\">'. $fn . '</TD><TD width=\"65%\" height=\"19\">' . $des . '</TD></TR>';
		fwrite($fp, $show);
		fclose($fp);
	}
	
	for ($i = 0; $i < $count; $i = $i + 4)
	{
		$gf = $cFunction[$i + 1];
		$file = $env['base'] . $gf . '.htm';
		$fp = fopen($file, 'a+');
		fwrite($fp, '</TABLE>');
		fclose($fp);
	}
}

// -------------------------------------------------------------------------
// show class

function class_decl($fp, $template, $class)
{
	fwrite($fp, "<PRE class=\"syntax\"><B>");
	if (isset($template))
		fwrite($fp, htmlspecialchars(trim($template->header)) . "<BR>");
	fwrite($fp, "$class->keyword $class->name");
	if (isset($class->bases))
	{
		fwrite($fp, " : <BR>\n");
		foreach ($class->bases as $i => $base)
		{
			fwrite($fp, "&#x20;&#x20;");
			if ($i != 0)
				fwrite($fp, ",<BR>");
			if ($base->access)
				fwrite($fp, "$base->access ");
			fwrite($fp, $base->name);
		}
	}
	fwrite($fp, ";<BR/></B></PRE>\n");
}

function show_ctors($fp, $class, $file, $env)
{
	return show_fntable($fp, $class, $file, array_merge($env, array(
		'fntype' => "ctor", "title" => "构造函数", "name" => "Constructor", "desc" => "Description")));
}

function show_dtors($fp, $class, $file, $env)
{
	return show_fntable($fp, $class, $file, array_merge($env, array(
		'fntype' => "dtor", "title" => "析构函数", "name" => "Destructor", "desc" => "Description")));
}

function show_methods($fp, $class, $file, $env)
{
	show_fntable($fp, $class, $file, array_merge($env, array(
		'fntype' => "member", "title" => "方法列表", "name" => "Method", "desc" => "Description")));
}

function show_class($comment, $s, $env)
{
	global $cpp_path, $cpp_file;
	$class = $s->class;
	$file = $env['base'] . $class->name . '.htm';
	$fp = fopen($file, 'w');
	if (!$fp) {
		echo "---> ERROR: Create file `$file` failed!\n";
		return;
	}

	topic_start($fp, $comment, $class->name, $file, array_merge($env, array('nsdisp' => 'class ')));
	class_decl($fp, $s->template, $class);
	show_args($fp, $comment, $file, $env);
	show_desc($fp, $comment);
	show_remark($fp, $comment, $file, $env);
	{
		$base = $env['base'] . "$class->name/";
		@mkdir($base);
		$env2 = array_merge($env, array(
			'base' => $base, 'nsdisp' => "$class->name::"));
		show_ctors($fp, $class, $file, $env2);
		show_dtors($fp, $class, $file, $env2); 
		show_methods($fp, $class, $file, $env2);
	}
	topic_end($fp, $comment, $file, $env);
	
	insertSummary("paths", $class->name . '.htm', "" . $env['base'], "", "", mb_strpos(str_replace("\t", "    ", file_get_contents($cpp_path . "/" . $cpp_file)), "class " . $class->name, 0, "UTF-8"));
	
	fclose($fp);
	
	if (isset($s->class->sentences))
		foreach ($s->class->sentences as $s)
	{
		if (isset($s->comment))
		{
			$comment = $s->comment;
			if (isset($comment->category))
				$env['category'] = $comment->category;
			if (isset($comment->ns))
			{
				$base = str_replace(array('::', '.', '\\'), array('/', '/', '/'), $comment->ns) . '/';
				$base = str_replace('//', '/', $base);
				@mkdir($base, 0700, true);
				$env['base'] = $base;
				$env['nsdisp'] = str_replace('/', '::', $base);
			}
			
			if (isset($comment->topic))
				$topic = $comment->topic;
		}
		else
		{
			if (!isset($base))
				$base = '';
			if (isset($s->macro))
				show_macro($comment, $s, $env);
			if (isset($s->class))
				show_class($comment, $s, $env);
			else if (isset($s->global))
				show_global_fn($comment, $s, $env);
			unset($comment);
		}
	}
}

// -------------------------------------------------------------------------

?>
