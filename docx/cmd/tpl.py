# -*- coding: utf-8 -*-
import string
import sys
import re

class Tpl(object):
	tpl = None
	def __init__(self, tpl):
		self.tpl = tpl

	def substitute(self, *args, **kwargs):
		if args:
			args = args[0]
		else:
			args = kwargs
			
		py = []
		tpl = self.tpl.split("\n")
		scheme = 0
		father = []
		for tpl_line in tpl:
			tpl_line = tpl_line.strip()
			if tpl_line.startswith("{") and tpl_line.endswith("}"):
				tpl_line = tpl_line[1:-1].strip()
				if tpl_line.strip() == "end":
					scheme -= 1
					continue
				py.append("%s%s:" % ('\t'*scheme, tpl_line))
				scheme += 1
				if tpl_line.startswith("for"):
					if len(father) > scheme:
						father = father[:scheme]
					last_s = tpl_line.rfind(' ')
					father.append(tpl_line[last_s+1:])
			else:
				extend = ""
				if scheme > 0:
					ret = re.findall(r"\$([\w\[\]_'\"]+)", tpl_line)
					if len(ret) > 0:
						extend = " %% (%s)" % ", ".join(ret)
					tpl_line = re.sub(r"\$([\w\[\]_'\"]+)", "%s", tpl_line)
				py.append("%s_html.append('''%s'''%s)" % ("\t"*scheme, tpl_line, extend))
		return self.sandboxes(py, args)
		
	def sandboxes(self, _html_py, _args):
		vars().update(_args)
		_html_py = '\n'.join(_html_py)
		_html = []
		exec(_html_py)
		print _html_py
		return string.Template("\n".join(_html)).substitute(_args)
		

if __name__ == "__main__":
	f = open("%s/template/test.html" % sys.path[0], "r")
	tpl = f.read()
	f.close()
	tpl = Tpl(tpl)
	html = tpl.substitute(echo="!!echo!!", c=['1', 'c', 's'], a='w', e=dict(s="ss", b="bb"))
	print html
