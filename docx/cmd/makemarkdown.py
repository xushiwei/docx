#!/usr/bin/env python
# -*- coding: utf-8 -*-
#  @arg: qiniu/docx/docx/cmd/godir github.com
# @arg: qiniu/docx/docx/cmd/godir api > qiniu/docx/docx/cmd/index.md
# @&&: open index.md
#  @&&: open out/
import gojspp
import shutil
import os
import sys
import tpl

outdir = "%s/md_out" % sys.path[0]
tpldir = "%s/template" % sys.path[0]

class cls(object):
	def __init__(self, **kwargs):
		self.__dict__.update(kwargs)

def get_template(name):
	f = open("%s/%s.md" % (tpldir, name))
	template = tpl.Tpl(f.read()).substitute
	f.close()
	return template

def do(filepath, filter_regex):
	if os.path.exists(outdir):
		shutil.rmtree(outdir)
	rdir, dirs, _ = os.walk(tpldir).next()
	dirs = [('%s/%s' % (rdir, d), "%s/%s" % (outdir, d)) for d in dirs]
	for src, dst in dirs:
		shutil.copytree(src, dst)
	datas = gojspp.do(filepath, filter_regex)
	make(datas)

def make(datas):
	html = []
	for data in datas:
		if 'func' in data:
			for func in data['func'].values():
				if not 'doc' in func:
					continue
				html.append(template.func(func))
		
		if 'typedef' in data:
			for typedef in data['typedef'].values():
				if not 'doc' in typedef:
					if "struct" in typedef and "func" in typedef["struct"]:
						if len([i for i in typedef["struct"]["func"].values() if 'doc' in i]) <= 0:
							continue
					else:
						continue
				h = template.type(typedef)
				html.append(h)
	
	print '\n'.join(html)

template = cls(
	func = get_template("function"),
	type = get_template("type"),
)

if __name__ == "__main__":
	if len(sys.argv) <= 1:
		exit("miss file")

	filepath = sys.argv[1]
	filter_regex = None if len(sys.argv) < 3 else sys.argv[2]
	do(filepath, filter_regex)
