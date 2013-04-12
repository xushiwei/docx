#!/usr/bin/env python
# -*- coding: utf-8 -*-
# @arg: qiniu/docx/docx/cmd/godir api
#  @arg: qiniu/docx/docx/cmd/godir api > qiniu/docx/docx/cmd/index.html
# @&&: open out/github.com/qiniu/api/rs/PutPolicy.html
import gojspp
import sys
import tpl
import re
import os

domain = "http://chzyer.github.io/api/"
domain = "file:///Volumes/CheneyHome/qiniu/docx/docx/cmd/out/"

def get_template(name):
	f = open("%s/template/%s.html" % (sys.path[0], name))
	template = tpl.Tpl(f.read()).substitute
	f.close()
	return template

def save(path, data):
	if path.startswith("/"):
		path = paht[1:]
	path = "%s/out/%s" % (sys.path[0], path)
	dirpath = path[:path.rfind("/")]
	if not os.path.exists(dirpath):
		os.makedirs(dirpath)
	f = open(path, "w")
	f.write(data.encode("utf-8"))
	f.close()

def save_to_base(path, content, data):
	global template
	body = template.body(content=content, detail=data)
	save(path, body)

class cls(object):
	def __init__(self, **kwargs):
		self.__dict__.update(kwargs)

template = cls(
	func = get_template("function"),
	type = get_template("type"),
	map = get_template("map"),
	body = get_template("content_and_detail"),
	base = get_template("base"),
)

def do(filepath, filter_regex):
	datas = gojspp.do(filepath, filter_regex)
	content = make_content(datas)
	make(datas, content)

def make(datas, content):
	for data in datas:
		dirpath = data["pkg_path"]
		if "func" in data:
			for func in data["func"].values():
				filename = "%s.html" % (func["name"])
				html = template.func(func)
				save_to_base(dirpath + "/" + filename, content, html)

		if "typedef" in data:
			for typedef in data["typedef"].values():
				filename = "%s.html" % typedef["name"]
				typedef['pkg'] = data['pkg']
				html = template.type(typedef)
				save_to_base(dirpath + "/" + filename, content, html)

				if "struct" in typedef and "func" in typedef["struct"]:
					for func in typedef["struct"]["func"].values():
						filename = "%s_%s.html" % (typedef["name"], func["name"])
						func["pkg"] = data["pkg"]
						func["name"] = "%s.%s" % (typedef["name"], func["name"])
						html = template.func(func)
						save_to_base(dirpath + "/" + filename, content, html)

def make_content(datas):
	mm = {}
	lines = {}
	for data in datas:
		path = data['pkg_path']
		lib = getto(mm, path)
		
		if "func" in data:
			for func in data["func"].values():
				lib['%s|%s%s/%s.html' % (func["name"], domain, path, func["name"])] = None

		if "typedef" in data:
			for typedef in data["typedef"].values():
				mix_name = '%s|%s%s/%s.html' % (typedef["name"], domain, path, typedef["name"])
				lib[mix_name] = None

				if "struct" in typedef and "func" in typedef["struct"]:
					for func in typedef["struct"]["func"].values():
						if not lib[mix_name]:
							lib[mix_name] = dict()
						lib[mix_name]['%s|%s%s/%s_%s.html' % (func["name"], domain, path, typedef["name"], func["name"])] = None

	return template.map(key=mm.keys()[0], value=mm.values()[0], map = template.map, domain=domain)

def getkey(path):
	if path.find('/') > 0:
		return path[: path.find('/')]
	return path

def getto(m, path):
	r = 'm["%s"]' % '"]["'.join(path.split('/'))
	try:
		return eval(r)
	except KeyError:
		pass
	mm = m
	while len(path) > 1:
		key = getkey(path)
		if not key in mm:
			mm[key] = dict()
		mm = mm[key]
		path = path[len(key)+1:]
	return eval(r)

if __name__ == "__main__":
	if len(sys.argv) <= 1:
		exit("miss file")

	filepath = sys.argv[1]
	filter_regex = None if len(sys.argv) < 3 else sys.argv[2]
	do(filepath, filter_regex)
