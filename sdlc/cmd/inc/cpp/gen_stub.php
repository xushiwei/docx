<?php
	/*
		Generate Server Stubs of RPC
	*/
	function genCall($func, $indent)
	{
		global $current_server;
	
		$funcname = $func->name;
		$rettype = getRetType($funcname);
		$args_set = parseArgs(@$func->args);

		echo "${indent}${rettype} _result;\n";
		
		// decode args
		$arg_count = count($args_set);
		
		if ($arg_count)
		{
			foreach($args_set as $var => $tp)
			{
				$typename = mapType($tp, "");
				echo "${indent}$typename $var;\n";
			}
			echo "\n";
		
			$index = false;
			
			if ($arg_count == 1)
			{
				echo "${indent}const bool _fOk = NS_CERL_IO::get(_ar, $var);\n\n";
			}
			else
			{
				echo "${indent}const bool _fOk = ";
				foreach($args_set as $var => $type)
				{
					if ($index)
						echo "\n${indent}\t\t&& ";
					echo "NS_CERL_IO::get(_ar, $var)";
					$index = true;
				}
				echo ";\n\n";
			}
			echo "${indent}_result = cerl::code_error;\n";
			echo "${indent}CERL_ASSERT(_fOk && \"$current_server::_handle_call - $funcname\");\n";
			echo "${indent}if (_fOk)\n";
		}
		
		$indent2 = $arg_count ? ($indent . "\t") : $indent;
		
		echo "${indent2}${funcname}(_mail, _result";
			
		foreach($args_set as $var => $tp)
			echo ", ${var}";
			
		echo ");\n";
		
		if ($arg_count)
		{
			echo "${indent}else\n";
			echo "${indent}\t_result = cerl::code_format_error;\n";
		}
		echo "\n${indent}cerl::reply(_self, _mail, _fid, _result);\n";
	}
	
	function genCast($func, $indent)
	{
		global $current_server;
		
		$funcname = $func->name;
		$async = @$func->async;
		$args_set = parseArgs(@$func->args);
		
		// decode args
		$arg_count = count($args_set);
		
		if ($arg_count)
		{
			foreach($args_set as $var => $tp)
			{
				$typename = mapType($tp, "");
				echo "${indent}$typename $var;\n";
			}
			echo "\n";
		
			if ($arg_count == 1)
			{
				echo "${indent}const bool _fOk = NS_CERL_IO::get(_ar, $var);\n\n";
			}
			else
			{
				$index = false;
				echo "${indent}const bool _fOk = ";
				foreach($args_set as $var => $type)
				{
					if ($index)
						echo "\n${indent}\t\t&&";
					echo "NS_CERL_IO::get(_ar, $var)";
					$index = true;
				}
				echo ";\n\n";
			}
			echo "${indent}CERL_ASSERT(_fOk && \"$current_server::_handle_cast - $funcname\");\n";
			echo "${indent}if (_fOk)\n";
		}
		
		$indent2 = $arg_count ? ($indent . "\t") : $indent;
		
		echo "${indent2}${funcname}(_mail";
		foreach($args_set as $var => $tp)
			echo ", ${var}";
		echo ");\n";
	}
	
	function enumFuncs($server,$indent, $cast)
	{
		global $current_server;
		$sentences = @$server->sentences;
		if (!$sentences)
			return;
		
		$once = true;
		
		foreach ($sentences as $sent)
		{
			$func = @$sent->function;
			if (!$func)
				continue;
			
			$name = $func->name;
			$async = @$func->async;
			$id = $func->id;
			$indent2 = $indent . "\t";
			$indent3 = $indent2 . "\t";
			
			if ($cast)
			{
				if ($async)
				{
					if ($once)
					{
						$once = false;
						echo "${indent}switch (_fid)\n";
						echo "${indent}{\n";
					}
					echo "${indent}case code_${name}:\n";
					echo "${indent2}{\n";
					
						genCast($func, $indent3);
					
					echo "${indent2}}\n";
					echo "${indent2}break;\n";
				}
				else
				{
					continue;
				}
			}
			else
			{
				if ($async)
					continue;
					
				if ($once)
				{
					$once = false;
					echo "${indent}switch (_fid)\n";
					echo "${indent}{\n";
				}	
				echo "${indent}case code_${name}:\n";
				echo "${indent2}{\n";
				
					genCall($func, $indent3);
					
				echo "${indent2}}\n";
				echo "${indent2}break;\n";
			}
		}
		
		if (false == $once)
		{
			echo "${indent}default:\n";
			if (!$cast)
				echo "${indent2}cerl::handle_call(_self, this, _mail, _fid, _ar);\n";
		}	
		
		$indentx = $once ? $indent : $indent2;
		if($once && !$cast)
			echo "${indentx}cerl::handle_call(_self, this, _mail, _fid, _ar);\n";
		$handl_func_name = $cast ? "_handle_cast" : "_handle_call";
		//echo "${indentx}CERL_TRACE(\"$current_server::$handl_func_name - Unknown FID: %.8X\\n\", _fid);\n";
		if (false == $once)
		{
			echo "${indent2}break;\n"; 
			echo "${indent}}\n";
		}
	}
	
	function genHandleCall($server, $indent)
	{
		$impl_class_name = $server->name ."Impl";
		
		$indent2 = $indent . "\t";
		$indent3 = $indent2 . "\t";
		echo "\n";
		echo "${indent}void cerl_call _handle_call(cerl::Mail* _mail, cerl::FID _fid, cerl::MessageReader& _ar)\n";
		echo "${indent}{\n";
			enumFuncs($server, $indent2, false/*sync calls*/);
		echo "${indent}}\n";
	}
	
	function genHandleCast($server, $indent)
	{
		$impl_class_name = $server->name ."Impl";
		
		$indent2 = $indent . "\t";
		$indent3 = $indent2 . "\t";
		echo "\n";
		echo "{$indent}void cerl_call _handle_cast(cerl::Mail* _mail, cerl::FID _fid, cerl::MessageReader& _ar)\n";
		echo "${indent}{\n";
			enumFuncs($server, $indent2, true/*async cast*/);
			
		echo "${indent}}\n";
	}
	
	function genMain($server, $indent, $ctor, $th)
	{
		global $class_postfix_impl,$class_postfix_stub;
		
		$impl_class_name = $server->name . $class_postfix_impl;
		$stub_class_name = $server->name . $class_postfix_stub;
		echo "\n";
		echo "${indent}static int cerl_callback _main$th(cerl::Process* _me, cerl::Mail* _param)\n";
		echo "${indent}{\n";
			$indent2 = $indent . "\t"; 
			
			if (count($ctor))
			{
				foreach($ctor as $var => $tp)
				{
					$typename = mapType($tp, "");
					echo "${indent2}$typename $var;\n";
				}
				echo "${indent2}{\n";
					$indent3 = $indent2 . "\t";
					echo "${indent3}cerl::MailPtr _initParam(_param);\n";
					echo "${indent3}cerl::MessageReader _ar(_me, _param);\n";
					echo "${indent3}bool _fOk = ";
					
					$index = 0;
					foreach($ctor as $var => $tp)
					{
						if ($index ++)
							echo "\n${indent3}\t\t&& ";
						echo "NS_CERL_IO::get(_ar, ${var})";
						
					}
					echo ";\n";
					echo "${indent3}CERL_ASSERT(_fOk && \"$stub_class_name::_main\");\n";
					echo "${indent3}if (!_fOk)\n";
						echo "${indent3}\treturn cerl::code_format_error;\n";
				echo "${indent2}}\n";
			}
			else
			{
				echo "${indent2}CERL_ASSERT(_param == NULL);\n";
			}
			
			echo "${indent2}${impl_class_name} _impl(_me";
			if (count($ctor))
			{
				foreach($ctor as $var => $tp)
					echo ", $var";
			}
			echo ");\n";
			
			echo "${indent2}return cerl::gen_server_run(_me, static_cast<${stub_class_name}*>(&_impl));\n";
		echo "${indent}}\n";
	}
	
	function genStart($server, $indent, $ctor, $th)
	{
		global $class_postfix_impl, $class_postfix_stub;
		$class_name = $server->name;
		$impl_class_name = $server->name . $class_postfix_impl;
		$stub_class_name = $server->name . $class_postfix_stub;
		echo "\n";
		echo "${indent}inline cerl::LocalProcess cerl_call ${impl_class_name}::_start(cerl::Process* _caller";
		
		if (count($ctor))
		{
			foreach($ctor as $var => $tp)
			{
				$typename = mapType($tp, "&");
				echo ", const $typename $var";
			}
		}
		echo ")\n";
		
		echo "${indent}{\n";
			$indent2 = $indent . "\t";
			if (count($ctor))
			{
				echo "${indent2}cerl::MessageWriter _wr(_caller);\n";

				foreach($ctor as $var => $tp)
					echo "${indent2}NS_CERL_IO::put(_wr, $var);\n"; 
				
				echo "${indent2}cerl::Mail* const _param = cerl_new_mail(_caller, _wr.close());\n";
			}
			
			$param_arg = count($ctor) ? "_param" : "NULL";
			echo "${indent2}return_cerl_dbg_spawn(cerl_node(_caller), ${stub_class_name}::_main$th, $param_arg, \"${class_name}\", ${stub_class_name}::_fid2name);\n";
		echo "${indent}}\n";
	}
	
	
	
	function genStubClass($server, $indent)
	{
		global $class_postfix_stub, $class_postfix_impl;
		global $ctors;
		
		$class_name = $server->name;
		$stub_class_name = $class_name . $class_postfix_stub;
		echo "\n${indent}class $stub_class_name : public ${class_name}${class_postfix_impl}\n";
		echo "${indent}{\n";
		echo "${indent}public:\n";
		
			$indent2 = $indent . "\t";
		
			genHandleCast($server, $indent2);
			genHandleCall($server, $indent2);
			
			$ctors = parseCtors($server);
			
		
			if (count($ctors))
			{
				$nth = 0;
				foreach ($ctors as $ctor)
				{
					$postfix = ($nth == 0) ? "" : "$nth";
					genMain($server, $indent2, $ctor, $postfix);
					$nth ++;
				}
			}
			else
			{
				genMain($server, $indent2, NULL, "");
			}
			
		echo "${indent}};\n";
	}
	
	$ctors ;
	$header = strtoupper("sdl_${module}_stub_h");
?>
<?php
	global $ctors;
	
	ob_start();
	foreach (@$doc->sentences as $sent)
	{
		$server = @$sent->server;
		if (!$server)
			continue;
		
		$svr_name = $server->name;
		$current_server = $svr_name;
		
		$header = strtoupper("${module}_${svr_name}_stub_h");
		
		echo "/*\n";
		echo "\tDescription: 	Do not edit this file manually\n";	
		echo "\tAuthor:			SDL Compiler\n";
		/*echo "\tDate:			";
		$datetime = time() + 28800; echo date("Y-M-jS H:i:s\n",$datetime);*/
		echo "*/\n\n";
		echo "#ifndef $header\n";
		echo "#define $header\n";
		$guard = strtoupper("${module}_${svr_name}_impl_h");
		echo "\n#ifndef $guard\n";
		echo "#include \"${svr_name}Impl.h\"\n";
		echo "#endif\n";

		echo "\nnamespace $module {\n";

		genStubClass($server, "");
		echo "\n";
		
		if (count($ctors))
		{
			$nth = 0;
			foreach($ctors as $ctor)
			{
				$postfix = ($nth == 0) ? "" : "$nth";
				genStart($server, "", $ctor, $postfix);
				$nth ++;
			}
			
		}
		else
		{
			genStart($server, "", NULL, "");
		}
			
		echo "\n} //namespace\n";
		echo "\n#endif /* $header */ \n";
		
		$file_name = $svr_name . "Stub.h";
		$fd = fopen($file_name, 'w');
		fwrite($fd, ob_get_contents());
		fclose($fd);
		ob_clean();
	}
	ob_end_flush();
?>