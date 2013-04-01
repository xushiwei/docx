<?php
	function processVars($vars)
	{
		if ($vars)
		{
			foreach ($vars as $var)
			{
				processType($var->type);
				echo " $var->name;\n";
			}
		}
	}
	
	function processType($type)
	{
		$named_type = @$type->named_type;
		if ($named_type)
		{
			echo "  $named_type->name";
		}
		else
		{
			echo "  {\n";
			$coded_type = @$type->coded_type;
			if ($coded_type)
			{
				foreach ($coded_type->items as $it)
				{
					echo "    code: $it->code {\n";
					processVars(@$it->vars);
					echo "    }\n";
				}
			}
			else
			{
				processVars(@$type->struct->vars);
			}
			echo "  }";
		}
		$array = @$type->array;
		if ($array)
		{
			$size = @$array->size;
			echo "[$size]";
		}
	}
	
	function processSent($sent)
	{
		$codedef = @$sent->codedef;
		if ($codedef)
		{
			echo "codedef: $codedef->name = $codedef->value\n\n";
			return true;
		}
		else
		{
			$typedef = @$sent->typedef;
			if ($typedef)
			{
				echo "typedef: $typedef->name = {\n";
				processType($typedef->type);
				echo "\n}\n\n";
				return true;
			}
			return false;
		}
	}
	
	function processFunction($func)
	{
		echo "[id=$func->id] ";
		if (@$func->async)
			echo "[async] ";
		echo "function: $func->name(\n";
		processVars(@$func->args);
		echo ")";
		$ret = @$func->type;
		if ($ret)
		{
			echo " ->\n";
			processType($ret);
		}
		echo "\n\n";
	}
	
	function processServer($server)
	{
		echo "server: $server->name = {\n\n";
		foreach (@$server->sentences as $sent)
		{
			if (!processSent($sent))
				processFunction($sent->function);
		}
		echo "}\n\n";
	}
	
	echo "\nmodule: $doc->module\n\n";
	foreach (@$doc->sentences as $sent)
	{
		if (!processSent($sent))
			processServer($sent->server);
	}
?>
