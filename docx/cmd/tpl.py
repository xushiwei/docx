# -*- coding: utf-8 -*-
import sys
import re
_r = re.compile(r"\$([\w_'\"\[\]]+\]|\w+[a-zA-Z0-9])")
class Tpl(object):
	def __init__(self, tpl):
		self.tpl = [line.strip() for line in tpl.split("\n")]

	def substitute(self, *args, **kwargs):
		py, level = [], 0
		args = args[0] if len(args)>0 else kwargs
		for tpl_line in self.tpl:
			if tpl_line.startswith("{") and tpl_line.endswith("}"):
				tpl_line = tpl_line[1:-1].strip()
				level_change = 1
				if tpl_line == "end":
					level_change = -1
				elif tpl_line == "else" or tpl_line.startswith("elif"):
					level -= 1
				elif tpl_line.startswith("%"):
					level_change = 0
					
				tpl_line = tpl_line[1:] if tpl_line.startswith("%") else "%s:" % tpl_line 
				if level_change >= 0:
					py.append("%s%s" % ('\t' * level, tpl_line.strip()))
				level += level_change
				continue
			ret = _r.findall(tpl_line)
			extend = "" if len(ret)<=0 else " %% (%s)" % ", ".join(ret)
			py.append("%s_html.append('''%s'''%s)" % ('\t' * level, _r.sub("%s", tpl_line), extend))
		return self.sandboxes('\n'.join(py), args)
		
	def sandboxes(self, _html_py, _args):
		vars().update(_args)
		_html = []
		isset, echo = self.isset(_args), _html.append
		tpl = self.template(_html)
		if _args.get("debug", False):
			print _html_py
		exec(_html_py)
		return "\n".join(_html)
	
	def isset(self, args):
		return lambda x: x in args
	
	def template(self, _html):
		def wrapper(t, **kwargs):
			_html.append(t(kwargs))
			return
		return wrapper

if __name__ == "__main__":
	_ = lambda x: x
