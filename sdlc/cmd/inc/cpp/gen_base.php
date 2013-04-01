<?php
	/*
		Generate Base Class of servers and proxys including namespace scoped code definitions
	*/
	//include 'functions.php';
	
	function putCodedItems($namespace, $items, $indent)
	{
		global $module;
		global $builtin_codes;
		global $typeset, $codeset;
		
		echo "${indent}NS_CERL_IO::put(os, val._code);\n";
		$flagNeedSwitch = 0;
		foreach($items as $item)
		{
			$code_name = mapCodeName($item->code);
			$vars = @$item->vars;
			if ($vars)
			{
				if(0 == $flagNeedSwitch++)
				{
					echo "${indent}switch(val._code)\n";
					echo "${indent}{\n";
				}
				echo "{$indent}case $code_name:\n";
				foreach($vars as $var)
				{
					$var_member = "";
					if ("ok" == $item->code)
						$var_member = "val.$var->name";
					else
						$var_member = "val.$item->code.$var->name";
						
					echo "{$indent}\tNS_CERL_IO::put(os, $var_member);\n";
				}
				echo "{$indent}\tbreak;\n";
			}
		}
		
		if ($flagNeedSwitch)
		{
			echo "${indent}default:\n";
			echo "${indent}\tbreak;\n";
			echo "${indent}}\n";
		}
		echo "{$indent}NS_CERL_IO::put_vt_null(os);\n";
	}
	
	function getCodedItems($namespace, $items, $indent)
	{
		global $module;
		global $builtin_codes;
		
		$indent2 = $indent;
		echo "${indent2}if (!NS_CERL_IO::get(is, val._code))\n";
		echo "${indent2}\treturn false;\n\n";
		
			$indent3 = $indent2 . "\t";
			
			// since we have check enum types , so this always need 'switch'
			echo "${indent2}switch(val._code)\n";
			echo "${indent2}{\n";
			
			$pure_codes = array();
			foreach($items as $item)
			{
				$code_name = mapCodeName($item->code);
				$vars = @$item->vars;
				if (!$vars)
				{
					$pure_codes[] = $code_name;
					continue;
				}
				else
				{
					if (count($pure_codes)) // make neibor $pure_codes share "case xx:"
					{
						foreach($pure_codes as $pc)
							echo "{$indent2}case $pc:\n";
						echo "{$indent3}return NS_CERL_IO::check_vt_null(is);\n";
						$pure_codes = array();
					}
					
					echo "{$indent2}case $code_name:\n";
					echo "{$indent3}return ";
					
					$index = 0;
					foreach($vars as $var)
					{			
						if ($index ++)
							echo "\n${indent3}\t&& ";
						$var_member = "";
				
						if ("ok" == $item->code)
							$var_member = "val.$var->name";
						else
							$var_member = "val.$item->code.$var->name";
							
						echo "NS_CERL_IO::get(is, $var_member)";
					}
					if ($index)
						echo "\n${indent3}\t&& ";
					echo "NS_CERL_IO::check_vt_null(is);\n";
				}
			}
			
			// since we have check enum types , so this always need 'switch'	
			echo "${indent2}default:\n";
			echo "${indent2}\treturn false;\n";
			echo "${indent2}}\n";
	}
	
	function serializeUserType($namespace, $userdef, $indent)
	{
		global $module, $current_server, $derived_types;
		$type_type = @$userdef->type;
		$coded_type = @$type_type->coded_type;
		$struct = @$type_type->struct;
		
		if (!$coded_type && !$struct)
			return;
		if ($coded_type && isEnum($coded_type))
			return;
			
		$type_name = $userdef->name;
		if (@$derived_types[$current_server][$type_name])
			return;
			
		$indent2 = $indent . "\t";
		
		echo "${indent}\ntemplate <class OutputStreamT>\n";
		echo "${indent}inline void cerl_call put(OutputStreamT& os, const ${namespace}::$type_name& val)\n{$indent}{\n";
			echo "${indent2}IoTypeTraits<${namespace}::$type_name>::putType(os);\n";
			if ($coded_type)
			{
				putCodedItems($namespace,$coded_type->items, $indent2);
			}
			else
			{
				$vars = $struct->vars;
				foreach ($vars as $var)
					echo "{$indent2}NS_CERL_IO::put(os, val.$var->name);\n";
				echo "${indent2}NS_CERL_IO::put_vt_null(os);\n";
			}
		echo "${indent}}\n";
		////////////////////////////////////////////////////////////////////////////////////////////
		echo "\n";
		echo "${indent}template <class InputStreamT>\n";
		echo "${indent}inline bool cerl_call get(InputStreamT& is, ${namespace}::$type_name& val)\n{$indent}{\n";
			echo "${indent2}if (!IoTypeTraits<${namespace}::$type_name>::getType(is))\n";
			echo "${indent2}\treturn false;\n";
			if($coded_type)
			{
				getCodedItems($namespace,$coded_type->items, $indent2);
			}
			else
			{
				$vars = $struct->vars;
				echo "${indent2}return ";
				$index = 0;
				foreach ($vars as $var)
				{
					if ($index ++)
						echo "\n${indent2}\t&& ";
					echo "NS_CERL_IO::get(is, val.$var->name)";
				}
				
				echo "\n${indent2}\t&& ";
				echo "NS_CERL_IO::check_vt_null(is);\n";
			}
		echo "${indent}}\n////////////////////////////////////////////\n";
	}
	
	// assume RetType always be coded_type
	function serializeRetType($namespace, $func, $indent)
	{
		global $module;
		global $builtin_codes;
		global $current_server, $derived_types;
		global $cpp_keywords;
		
		$type = @$func->type;
		$async = @$func->async;
		if($async)
		{
			if ($type)
				die("\nERROR: You are expecting the 'async' function '$func->name' return a value!\n");
			return;
		}
		else if (!$type)
			die("\nERROR: Are you sure you have given 'sync' function '$func->name' a return value?\n");
		
		$retTyName = getRetType($func->name);
		
		if (@$derived_types[$current_server][$retTyName])
			return;
		$items = $type->coded_type->items;
		
		echo "${indent}\ntemplate <class OutputStreamT>\n";
		echo "${indent}inline void cerl_call put(OutputStreamT& os, const ${namespace}::$retTyName& val)\n{$indent}{\n";
			$indent2 = $indent . "\t";
			echo "${indent2}NS_CERL_IO::put(os, val._code);\n";
			$flagNeedSwitch = 0;
			foreach($items as $item)
			{
				$code_name = mapCodeName($item->code);
				$vars = @$item->vars;
				if ($vars)
				{
					if(0 == $flagNeedSwitch++)
					{
						echo "${indent2}switch(val._code)\n";
						echo "${indent2}{\n";
					}
					echo "{$indent2}case $code_name:\n";
					//echo "{$indent2}\t{\n";
					foreach($vars as $var)
					{
						$var_member = "";
					
						if ("ok" == $item->code)
							$var_member = "val.$var->name";
						else
						{
							$ns_code_name = $item->code;
							if (@$cpp_keywords[$item->code])
								$ns_code_name = "_" . $ns_code_name;
							$var_member = "val.$ns_code_name.$var->name";
						}
							
						echo "{$indent2}\tNS_CERL_IO::put(os, $var_member);\n";
					}
					//echo "{$indent2}\t}\n";
					echo "{$indent2}\tbreak;\n";
				}
			}
			if ($flagNeedSwitch)
			{
				echo "${indent2}default:\n";
				echo "${indent2}\tbreak;\n";
				echo "${indent2}}\n";
			}
		echo "${indent}}\n";
		////////////////////////////////////////////////////////////////////////////////////////////
		echo "\n";
		echo "${indent}template <class InputStreamT>\n";
		echo "${indent}inline bool cerl_call get(InputStreamT& is, ${namespace}::$retTyName& val)\n{$indent}{\n";
			$indent2 = $indent . "\t";
			
			$only_code = true;
			foreach($items as $item)
			{
				if(@$item->vars)
				{
					$only_code = false;
					break;
				}
			}
			
			if ($only_code)
			{
				echo "${indent2}return NS_CERL_IO::get(is, val._code);\n";
				echo "${indent}}\n////////////////////////////////////////////\n";
				return;
			}
			
			echo "${indent2}if (!NS_CERL_IO::get(is, val._code))\n";
			echo "${indent2}\treturn false;\n";
				$indent3 = $indent2 . "\t";
				
				$flagNeedSwitch = 0;
				foreach($items as $item)
				{
					$code_name = mapCodeName($item->code);
					
					$vars = @$item->vars;
					if ($vars)
					{
						if(0 == $flagNeedSwitch++)
						{	
							echo "\n${indent2}switch(val._code)\n";
							echo "${indent2}{\n";
						}
						echo "{$indent2}case $code_name:\n";
						//echo "{$indent2}\t{\n";
						echo "{$indent2}\treturn ";
						$index = 0;
						foreach($vars as $var)
						{							
							$var_member = "";
							if ("ok" == $item->code)
								$var_member = "val.$var->name";
							else
							{
								$ns_code_name = $item->code;
								if (@$cpp_keywords[$item->code])
									$ns_code_name = "_" . $ns_code_name;
								$var_member = "val.$ns_code_name.$var->name";
							}
							if ($index ++)
								echo "\n{$indent2}\t&& ";
							echo "NS_CERL_IO::get(is, $var_member)";
						}
						echo ";\n";
						//echo "{$indent2}\t}\n";
						//echo "{$indent2}\tbreak;\n";
					}
				}
				if ($flagNeedSwitch)
				{
					echo "${indent2}default:\n";
					echo "${indent2}\treturn true;\n";
					//echo "${indent2}\tbreak;\n";
					echo "${indent2}}\n";
				}
				else
				{
					echo "${indent2}return true;\n";
				}
			//echo "${indent2}return fGood;\n";	
		echo "${indent}}\n////////////////////////////////////////////\n";
	}
	
	function genBaseClass($server, $indent)
	{
		global $class_postfix_base;
		$class_name = $server->name . $class_postfix_base;
		echo "\n${indent}class $class_name : public cerl::GenServer\n";
		echo "${indent}{\n";
		echo "${indent}public:\n";
		
		$indent2 = $indent . "\t";
		$sentences = @$server->sentences;
		if ($sentences)
		{
			$func_array = processCodes($server, $indent);
			genFunc2Name($func_array, $indent);
			processRetTypes($server, $indent);
			foreach ($sentences as $sent)
			{
				if (!scanSent($sent, $indent2, false))
					continue;
			}
		}
		echo "${indent}};\n";
	}
?>
<?php
	ob_start();
	
	$header = strtoupper("sdl_${module}_base_h");
	
	echo "/*\n";
	echo "\tDescription: 	Do not edit this file manually\n";	
	echo "\tAuthor:			SDL Compiler\n";
	/*echo "\tDate:			";
	$datetime = time() + 28800; echo date("Y-M-jS H:i:s\n",$datetime);*/
	echo "*/\n\n";
	echo "#ifndef $header\n";
	echo "#define $header\n";
	echo "\n#ifndef CERL_GENSERVER_H\n";
	echo "#include <cerl/GenServer.h>\n";
	echo "#endif\n";
	
	echo "\n#pragma pack(1)\n";
	echo "\nnamespace $module {\n\n";
	///////////////////////////////////////////////

	echo "enum { code_ok = cerl::code_ok };\n";
	echo "enum { code_error = cerl::code_error };\n";
	echo "enum { code_true = cerl::code_true };\n";
	echo "enum { code_false = cerl::code_false };\n";

	foreach (@$doc->sentences as $sent)
	{
		if (!scanSent($sent, "", true))
		{
			$server = $sent->server;
			$current_server = $server->name;
			$whole_name = "$module::$server->name";
			if (@$typeset[$whole_name])
				die("\n---ERROR: There's a type name conflict with the coming server named '$server->name'!");
			
			genBaseClass($server, "");
		}
	}
	
	echo "\n} //namespace\n";
	echo "\n#pragma pack()\n";
	
	echo "\n// Generated serialization of user types\n";
	echo "//////////////////////////////////////////////////////////////////////////\n";
	echo "\nNS_CERL_IO_BEGIN\n";

	foreach (@$doc->sentences as $docsent)
	{
		global $class_postfix_base;
		$namespace = $module;
		$current_server = $module;
		if (typedefSent($docsent))
		{
			serializeUserType($namespace, $docsent->typedef, "");
			continue;
		}
		else
		{
			$server = @$docsent->server;
			if (!$server)
				continue;

			$namespace .= "::";
			$namespace .= $server->name;
			$namespace .= $class_postfix_base;
			$sentences = @$server->sentences;
			
			$current_server = $server->name;
			
			if (!$sentences)
				continue;
			
			foreach ($sentences as $sent)
			{
				if (@$sent->ctor)
					continue;
					
				if (typedefSent($sent))
				{
					serializeUserType($namespace, $sent->typedef, "");
				}
				else
				{
					$func = @$sent->function;
					if ($func)
						serializeRetType($namespace, $func, "");
				}
			}
		
		}
	}
		
	echo "\nNS_CERL_IO_END\n";
	echo "//////////////////////////////////////////////////////////////////////////\n";
	echo "#endif /* $header */ \n";
		
	$file_name = "sdl_${module}_base.h";
	$fd = fopen($file_name, 'w');
	fwrite($fd, ob_get_contents());
	fclose($fd);
	ob_clean();
	ob_end_flush();
?>