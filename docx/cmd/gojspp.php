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
			$decls2[] = $decl;
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
	$doc->decls = $decls2;
	if (isset($imports)) {
		$doc->imports = $imports;
	}
	if (isset($embimports)) {
		$doc->embimports = $embimports;
	}
}

gojspp($doc);
print_r($doc);

