#!/usr/bin/env python
# -*- coding: utf-8 -*-
#  @arg: qiniu/docx/docx/cmd/godir api 
# @arg: qiniu/docx/docx/cmd/godir api
#  @&&: open index.html
# @&&: open out/github.com/qiniu/api/rs/Client.html
#  @&&: open out/github.com/qiniu/api/resumable/io/SetSettings.html
import gojspp
import sys
import tpl
import re
import os
import shutil

domain = "/api/"
domain = "/Volumes/CheneyHome/qiniu/docx/docx/cmd/out/"
# domain = "Y:\qiniu\docx\docx\cmd\out/"
outdir = "%s/out" % sys.path[0]
tpldir = "%s/template" % sys.path[0]

def format_content(content):
	if isinstance(content, list):
		content = "".join(content)
	
	content = re.sub(r"@ref{([^}]+)}", u"<a href='\\1.html'>参照\\1</a>", content)
	content = re.sub(r"@link{([^\|]+)\|([^}]+)}", "<a href='\\1'>\\2</a>", content)
	
	return content

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
	map2 = get_template("map2"),
)

def do(filepath, filter_regex):
	if os.path.exists(outdir):
		shutil.rmtree(outdir)
	rdir, dirs, _ = os.walk(tpldir).next()
	dirs = [("%s/%s" % (rdir, d), "%s/%s" % (outdir, d)) for d in dirs]
	for src, dst in dirs:
		shutil.copytree(src, dst)
	datas = gojspp.do(filepath, filter_regex)
	content = make_content(datas)
	make(datas, content)

def make(datas, content):
	for data in datas:
		dirpath = data["pkg_path"]
		if "func" in data:
			funcs = [data["func"][i] for i in sorted(data["func"].keys())]
			for func in funcs:
				filename = "%s.html" % (func["name"])
				func.update(dict(
					domain = domain,
					format = format_content,
				))
				html = template.func(func)
				save_to_base(dirpath + "/" + filename, content, html, func["name"])

		if "typedef" in data:
			for typedef in data["typedef"]:
				type_filename = "%s.html" % typedef["name"]

				typedef.update(dict(
					pkg = data["pkg"],
					domain = domain,
					format = format_content,
				))
				
				if "struct" in typedef and "construct" in typedef["struct"]:
					for construct in typedef["struct"]["construct"]:
						filename = "%s/%s.html" % (typedef["name"], construct["name"])
						construct.update(dict(
							pkg = data["pkg"],
							domain = domain,
							struct = typedef,
							format = format_content,
						))
						html = template.func(construct)
						save_to_base(dirpath + "/" + filename, content, html, construct["name"])

				if "struct" in typedef and "func" in typedef["struct"]:
					for func in typedef["struct"]["func"]:
						filename = "%s/%s.html" % (typedef["name"], func["name"])
						func.update(dict(
							pkg = data["pkg"],
							domain = domain,
							struct = typedef,
							format = format_content,
						))
						html = template.func(func)
						save_to_base(dirpath + "/" + filename, content, html, func["name"])

				html = template.type(typedef)
				save_to_base(dirpath + "/" + type_filename, content, html, typedef["name"])

def format_doci(datas):
	f = open("%s/template/content.doci" % sys.path[0])
	content = f.read().split("\n")
	f.close()
	
	result = []
	path = []
	content = filter(None, content)
	for c in content:
		level = len(c) - len(c.lstrip())
		c = c.strip()
		if len(path) <= level:
			path.append(c)
		else:
			path[level] = c
		
		p = '/'.join(path[:level])
		result.append(dict(path=p, p=p + '/' + c, name=c))

	for i in result:
		key = "%s/%s" % (i["path"], i["name"])
		i["package"] = not key in datas
	return template.map2(result=result, map=template.map2, domain=domain)

def make_content(datas):
	result = {}
	for data in datas:
		path = data["pkg_path"]
		if "func" in data:
			for func in data["func"].values():
				p = "%s/%s" % (path, func["name"])
				result[p] = func
		
		if "typedef" in data:
			for typedef in data["typedef"]:
				p = "%s/%s" % (path, typedef["name"])
				result[p] = typedef
				
				if "struct" in typedef and "func" in typedef["struct"]:
					for func in typedef["struct"]["func"]:
						p = "%s/%s/%s" % (path, typedef["name"], func["name"])
						result[p] = func
				
				if "struct" in typedef and "construct" in typedef["struct"]:
					for construct in typedef["struct"]["construct"]:
						p = "%s/%s/%s" % (path, typedef["name"], construct["name"])
						result[p] = construct

	return format_doci(result)
	mm = {}
	lines = {}
	for data in datas:
		path = data["pkg_path"]
		lib = getto(mm, path)
		
		if "func" in data:
			for func in data["func"].values():
				if not "doc" in func:
					continue
				lib["%s|%s%s/%s.html" % (func["name"], domain, path, func["name"])] = None

		if "typedef" in data:
			for typedef in data["typedef"]:
				if not "doc" in typedef:
					if "struct" in typedef and "func" in typedef["struct"]:
						if len([i for i in typedef["struct"]["func"] if "doc" in i]) <= 0:
							continue
					else:
						continue
				mix_name = "%s|%s%s/%s.html" % (typedef["name"], domain, path, typedef["name"])
				lib[mix_name] = None

				if "struct" in typedef and "func" in typedef["struct"]:
					for func in typedef["struct"]["func"]:
						if not "doc" in func:
							continue
						if not lib[mix_name]:
							lib[mix_name] = dict()
						lib[mix_name]["%s|%s%s/%s_%s.html" % (func["name"],
								domain, path, typedef["name"], func["name"])] = None

	print mm
	return template.map(key=mm.keys()[0], value=mm.values()[0], map = template.map, domain=domain)

def getkey(path):
	if path.find("/") > 0:
		return path[: path.find("/")]
	return path

def getto(m, path):
	r = "m['%s']" % "']['".join(path.split("/"))
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
