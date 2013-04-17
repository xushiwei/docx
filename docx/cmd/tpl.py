# -*- coding: utf-8 -*-
import sys
import re

_r = re.compile(r"\$([\w_'\"\[\]]+\]|\w*[a-zA-Z0-9])")
class Tpl(object):
	def __init__(self, tpl):
		self.tpl = [line.lstrip() for line in tpl.split("\n")]

	def substitute(self, *args, **kwargs):
		py, level = ['# -*- coding: utf-8 -*-'], 0
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
			extend = "" if len(ret)<=0 else " %% (_u(%s))" % "), _u(".join(ret)
			py.append("%s_html.append(u'''%s'''%s)" % ('\t' * level, _r.sub("%s", tpl_line), extend))
		return self.sandboxes('\n'.join(py), args)
		
	def sandboxes(self, _html_py, _args):
		vars().update(_args)
		_html = []
		isset, echo, _u = self.isset(_args), _html.append, self.u()
		tpl = self.template(_html)
		if _args.get("debug", False):
			print _html_py
		exec(_html_py)
		return ("\n".join(_html)).encode("utf-8", "ignore")
	
	def isset(self, args):
		return lambda x: x in args
	
	def template(self, _html):
		def wrapper(t, **kwargs):
			_html.append(t(kwargs))
			return
		return wrapper
	
	def u(self):
		def wrapper(a):
			if isinstance(a, str):
				return unicode(a, 'utf-8')
			if isinstance(a, unicode):
				return a
			return unicode(str(a), 'utf-8')
		return wrapper

if __name__ == "__main__":
	a = "你好"
	b = u"你的"
	print Tpl("dd你好$a$b").substitute(a=a, b=b, debug=True)
