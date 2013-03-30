<?php

function gostring($str) {

	$n = strlen($str);
	$str = substr($str, 1, $n-2);
	return $str;
}

function gosettype($args) {

	$n = count($args);
	for ($i = 0; $i < $n; $i++) {
		$arg = $args[$i];
		if (!isset($arg->type)) {
			for ($j = $i+1; $j < $n; $j++) {
				$v = $args[$j];
				if (isset($v->type)) {
					for ($t = $i; $t < $j; $t++) {
						$args[$t]->type = $v->type;
					}
					break;
				}
			}
			$i = $j;
		}
	}
}

$g_builtinTypes = array(
	"string", "float64", "float32",
	"int", "int8", "int16", "int32", "int64",
	"uint", "uint8", "uint16", "uint32", "uint64",
	"byte", "uintptr"
);

foreach ($g_builtinTypes as $builtin) {
	$builtins[$builtin] = true;
}

function gobuiltin($name) {
	global $builtins;
	return isset($builtins[$name]);
}

function goctor($func) {

	if (!isset($func->returns)) {
		return '';
	}

	$returns = $func->returns;
	$n = count($returns);
	if ($n > 0) {
		$ret = $returns[0];
		if (isset($ret->type->typeref)) {
			$typeref = $ret->type->typeref;
			if (!isset($typeref->ns)) {
				$name = $typeref->name;
				if (!gobuiltin($name)) {
					return $name;
				}
			}
		}
	}
	return '';
}

function gojspp($doc) {

	$decls = $doc->decls;
	$n = count($decls);

	for ($i = 0; $i < $n; $i++) {
		$decl = $decls[$i];
		if (isset($decl->func)) {
			unset($comments);
			for ($j = $i - 1; $j >= 0; $j--) {
				$v = $decls[$j];
				if (!isset($v->comment)) {
					break;
				}
				$comments[] = $v->comment;
			}
			$func = $decl->func;
			if (isset($comments)) {
				$func->doc = $comments;
			}
			if (isset($func->args)) {
				gosettype($func->args);
			}
			if (isset($func->returns)) {
				gosettype($func->returns);
			}
			if (isset($func->recvr)) {
				$typname = $func->recvr->type->name;
				$types[$typname]->methods[] = $func;
			} else {
				$typname = goctor($func);
				if ($typname === '') {
					$funcs[] = $func;
				} else {
					$types[$typname]->ctors[] = $func;
				}
			}
		} else if (isset($decl->typedef)) {
			$name = $decl->typedef->name;
			$types[$name]->typedef = $decl->typedef;
		} else if (isset($decl->import)) {
			$import = $decl->import;
			$pkg = gostring($import->pkg);
			if (isset($import->name)) {
				$name = $import->name;
			} else {
				$name = basename($pkg);
			}
			if ($name === '.') {
				$embimports[] = $pkg;
			} else {
				$imports[$name] = $pkg;
			}
		}
	}
	unset($doc->decls);
	if (isset($types)) {
		$doc->types = $types;
	}
	if (isset($funcs)) {
		$doc->funcs = $funcs;
	}
	if (isset($imports)) {
		$doc->imports = $imports;
	}
	if (isset($embimports)) {
		$doc->embimports = $embimports;
	}
}

gojspp($doc);
print_r($doc);

