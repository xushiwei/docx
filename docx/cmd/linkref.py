#!/usr/bin/env python
# -*- coding: utf-8 -*-
# @arg: README.md qiniu/docx/docx/cmd/godir api
import gojspp
import makehtml
import sys
import re

re_symbol = re.compile(r"`([\w\._]+)`")
domain = "http://chzyer.github.com/api"

def openfile(path):
	f = open("%s/%s" % (sys.path[0], path))
	content = f.read()
	f.close()
	return content

def do(struct_keys, struct_datas, domain):
	datas = gojspp.do(filepath, filterstring)
	keys, result = makehtml.make_tree(datas)
	content = openfile(gistpath)
	content = unicode(content, "utf-8")
	news = []
	ks = [(i.replace(".", "/"), i) for i in re_symbol.findall(content)]
	for k, kn in ks:
		try:
			c = (i for i in keys if i.endswith(k)).next()
			if (kn, c) in news:
				continue
			news.append((kn, c))
		except StopIteration:
			pass
	for i in news:
		o = u"`%s`" % i[0]
		n = u"[`%s`](%s/%s.html)" % (i[0], domain, i[1])
		content = content.replace(o, n)

if __name__ == "__main__":
	gistpath = sys.argv[1]
	filepath = sys.argv[2]
	filterstring = None
	if len(sys.argv) > 3:
		filterstring = sys.argv[3]

	
	f = open("%s/%s" % (sys.path[0], gistpath), "w")
	f.write(content.encode("utf-8", "ignore"))
	f.close()
