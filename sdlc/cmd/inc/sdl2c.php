<?php
	function processVars($vars, $indent)
	{
		if ($vars)
		{
			$indent .= "\t";
			foreach ($vars as $var)
			{
				echo $indent;
				processType($var->type, $indent);
				echo " $var->name;\n";
			}
		}
	}
	
	function processArgs($args, $indent, $need)
	{
		if ($args)
		{
			$indent .= "\t";
			foreach ($args as $arg)
			{
				if ($need)
					echo ",\n${indent}";
				else
					echo "\n${indent}";
				processType($arg->type, $indent);
				echo " $arg->name";
				$need = true;
			}
		}
	}
	
	function getStructCount($items)
	{
		$count = 0;
		if ($items)
		{
			foreach ($items as $it)
			{
				$vars = @$it->vars;
				if ($vars)
					++$count;
			}
		}
		return $count;
	}
	
	function processType($type, $indent)
	{
		$named_type = @$type->named_type;
		$array = @$type->array;
		if ($named_type)
		{
			global $builtin_types;
			$name = $named_type->name;
			$builtin = @$builtin_types[$name];
			if ($builtin)
				$name = $builtin;
			if ($array)
			{
				$size = @$array->size;
				if ($size)
					$name = "cerl::Array<$name, $size>";
				else
					$name = "cerl::BasicArray<$name>";
			}
			echo "$name";
		}
		else
		{
			if ($array)
				die("can't define an array of unnamed type.");
			echo "struct {\n";
			$coded_type = @$type->coded_type;
			if ($coded_type)
			{
				$items = @$coded_type->items;
				$count = getStructCount($items);
				$indent2 = $indent . "\t";
				echo "${indent2}cerl::Code _code;\n";
				if ($count > 1)
				{
					$indent3 = $indent2 . "\t";
					echo "${indent2}union {\n";
				}
				else
				{
					$indent3 = $indent2;
				}
				foreach ($coded_type->items as $it)
				{
					$vars = @$it->vars;
					if ($vars)
					{
						echo "${indent3}struct {\n";
						processVars($vars, $indent3);
						if ($it->code == "ok")
							$it_name = "";
						else
							$it_name = " " . $it->code;
						echo "${indent3}}$it_name;\n";
					}
				}
				if ($count > 1)
					echo "${indent2}};\n";
			}
			else
			{
				processVars(@$type->struct->vars, $indent);
			}
			echo "${indent}}";
		}
	}
	
	function processSent($sent, $indent)
	{
		$codedef = @$sent->codedef;
		if ($codedef)
		{
			echo "${indent}enum { code_$codedef->name = $codedef->value };\n";
			return true;
		}
		else
		{
			$typedef = @$sent->typedef;
			if ($typedef)
			{
				echo "\n${indent}typedef ";
				processType($typedef->type, $indent);
				echo " $typedef->name;\n";
				return true;
			}
			return false;
		}
	}

	function processFuncCodes($server, $indent)
	{
		$sentences = @$server->sentences;
		if ($sentences)
		{
			$indent .= "\t";
			foreach ($sentences as $sent)
			{
				$func = @$sent->function;
				if ($func)
					echo "${indent}enum { code_$func->name = $func->id };\n";
			}
		}
	}

	function getRetType($name)
	{
		$rettype = "_" . $name . "Result";
		$rettype[1] = strtoupper($rettype[1]);
		return $rettype;
	}

	function processRetType($func, $indent)
	{
		$async = @$func->async;
		if ($async)
			return;
		
		$ret = @$func->type;
		if ($ret)
		{
			echo "\n${indent}typedef ";
			processType($ret, $indent);
			$rettype = getRetType($func->name);
			echo " $rettype;\n";
		}
	}
	
	function processRetTypes($server, $indent)
	{
		$sentences = @$server->sentences;
		if ($sentences)
		{
			$indent .= "\t";
			foreach ($sentences as $sent)
			{
				$func = @$sent->function;
				if ($func)
					processRetType($func, $indent);
			}
		}
	}
	
	function processFunction($func, $indent)
	{
		$name = $func->name;
		$async = @$func->async;
		if ($async)
		{
			echo "\n${indent}/*[async]*/ cerl::Code $name(";
			$need = false;
		}
		else
		{
			$rettype = getRetType($name);
			echo "\n${indent}void $name(\n${indent}\t$rettype& result";
			$need = true;
		}
		processArgs(@$func->args, $indent, $need);
		echo "\n${indent});\n";
	}
	
	function processServer($server, $indent)
	{
		echo "\n${indent}class $server->name\n";
		echo "${indent}{\n";
		$indent2 = $indent . "\t";
		$sentences = @$server->sentences;
		if ($sentences)
		{
			processFuncCodes($server, $indent);
			processRetTypes($server, $indent);
			foreach ($sentences as $sent)
			{
				if (!processSent($sent, $indent2))
					processFunction($sent->function, $indent2);
			}
		}
		echo "${indent}};\n";
	}
	
	$module = $doc->module;
	$header = "sdl_${module}_h";
	$builtin_types = array(
		"UInt32" => "cerl::UInt32",
		"Int32" => "cerl::Int32",
		"String" => "cerl::String",
		"Char" => "cerl::Char",
		"Bool" => "cerl::Bool",
		"UInt16" => "cerl::UInt16",
		"Int16" => "cerl::Int16",
		"UInt8" => "cerl::UInt8"
	);
?>
#ifndef <?php echo "$header\n" ?>
#define <?php echo "$header\n" ?>

namespace <?php echo "$module\n" ?>
{
<?php
	foreach (@$doc->sentences as $sent)
	{
		if (!processSent($sent, "\t"))
			processServer($sent->server, "\t");
	}
?>
}

#endif /* <?php echo $header ?> */
