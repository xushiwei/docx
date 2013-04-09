#!/usr/bin/env python
# -*- coding: utf-8 -*-
# @arg: qiniu/docx/docx/cmd/go/src/io/io_api.go
import gojspp
import sys
import tpl
import re
import os

def get_template(name):
	f = open("%s/template/%s.html" % (sys.path[0], name))
	template = tpl.Tpl(f.read()).substitute
	f.close()
	return template

def make(pkg, name, data, template):
	path = "%s/out/%s" % (sys.path[0], pkg)
	if not os.path.exists(path):
		os.mkdir(path)
	f = open("%s/%s.html" % (path, name), 'w')
	head = template.head(title='%s/%s' % (pkg, name))
	f.write(template(body=data, head=head).encode("utf-8", "ignore"))
	f.close()

class cls(object):
	def __init__(self, **kwargs):
		self.__dict__.update(kwargs)

template = cls(
	func = get_template("function"),
)

if __name__ == "__main__":
	if len(sys.argv) <= 1:
		exit("miss file")

	filepath = sys.argv[1]
	data = gojspp.format_go2json(filepath)
	func = data["func"].values()[0]
	print func
	print template.func(func)
