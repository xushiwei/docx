#!/usr/bin/env python
# -*- coding: utf-8 -*-
# @arg: qiniu/docx/docx/cmd/godir api
#  @arg: qiniu/docx/docx/cmd/godir api > qiniu/docx/docx/cmd/index.html
#  @&&: open out/github.com/qiniu/api/fop/ImageView_MakeRequest.html
# @&&: open out/github.com/qiniu/api/rs/PutPolicy.html
import gojspp
import sys
import tpl
import re
import os
import shutil

domain = "/api/"
# domain = "file:///Volumes/CheneyHome/qiniu/docx/docx/cmd/out/"
# domain = "Y:\qiniu\docx\docx\cmd\out/"
outdir = "%s/out" % sys.path[0]
tpldir = "%s/template" % sys.path[0]

def get_template(name):
	f = open("%s/%s.html" % (tpldir, name))
	template = tpl.Tpl(f.read()).substitute
	f.close()
	return template

def save(path, data):
	if path.startswith("/"):
		path = paht[1:]
	path = "%s/%s" % (outdir, path)
	dirpath = path[:path.rfind("/")]
	if not os.path.exists(dirpath):
		os.makedirs(dirpath)
	f = open(path, "w")
	f.write(data)
	f.close()

def save_to_base(path, content, data, name):
	global template
	body = template.body(content=content, detail=data, domain=domain, name=name)
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
	if os.path.exists(outdir):
		shutil.rmtree(outdir)
	rdir, dirs, _ = os.walk(tpldir).next()
	dirs = [('%s/%s' % (rdir, d), "%s/%s" % (outdir, d)) for d in dirs]
	for src, dst in dirs:
		shutil.copytree(src, dst)
	datas = gojspp.do(filepath, filter_regex)
	content = make_content(datas)
	make(datas, content)

def make(datas, content):
	for data in datas:
		dirpath = data["pkg_path"]
		if "func" in data:
			for func in data["func"].values():
				if not 'doc' in func:
					continue
				filename = "%s.html" % (func["name"])
				func["domain"] = domain
				html = template.func(func)
				save_to_base(dirpath + "/" + filename, content, html, func["name"] + " Function")

		if "typedef" in data:
			for typedef in data["typedef"].values():
				if not 'doc' in typedef:
					if "struct" in typedef and "func" in typedef["struct"]:
						if len([i for i in typedef["struct"]["func"].values() if 'doc' in i]) <= 0:
							continue
					else:
						continue
				filename = "%s.html" % typedef["name"]

				typedef['pkg'] = data['pkg']
				typedef["domain"] = domain
				html = template.type(typedef)
				save_to_base(dirpath + "/" + filename, content, html, typedef["name"])

				if "struct" in typedef and "func" in typedef["struct"]:
					for func in typedef["struct"]["func"].values():
						if not 'doc' in func:
							continue
						filename = "%s_%s.html" % (typedef["name"], func["name"])
						func["pkg"] = data["pkg"]
						func["domain"] = domain
						func["struct"] = typedef
						html = template.func(func)
						save_to_base(dirpath + "/" + filename, content, html, func["name"])

def make_content(datas):
	mm = {}
	lines = {}
	for data in datas:
		path = data['pkg_path']
		lib = getto(mm, path)
		
		if "func" in data:
			for func in data["func"].values():
				if not 'doc' in func:
					continue
				lib['%s|%s%s/%s.html' % (func["name"], domain, path, func["name"])] = None

		if "typedef" in data:
			for typedef in data["typedef"].values():
				if not 'doc' in typedef:
					if "struct" in typedef and "func" in typedef["struct"]:
						if len([i for i in typedef["struct"]["func"].values() if 'doc' in i]) <= 0:
							continue
					else:
						continue
				mix_name = '%s|%s%s/%s.html' % (typedef["name"], domain, path, typedef["name"])
				lib[mix_name] = None

				if "struct" in typedef and "func" in typedef["struct"]:
					for func in typedef["struct"]["func"].values():
						if not 'doc' in func:
							continue
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
