#!/usr/bin/env python
# -*- coding: utf-8 -*-
# @arg: qiniu/docx/docx/cmd/godir api
#  @&&: open index.html
#  @&&: open out/github.com/qiniu/api/resumable/io/SetSettings.html
import gojspp
import sys
import tpl
import re
import os
import shutil

domain = "/api"
# domain = "/Volumes/CheneyHome/qiniu/docx/docx/cmd/out"
# domain = "Y:\qiniu\docx\docx\cmd\out/"
# domain = r"\\Ubuntu\CheneyHome\qiniu\docx\docx\cmd\out"
outdir = "%s/out" % sys.path[0]
tpldir = "%s/template" % sys.path[0]

re_var = re.compile(r"#([\w\.]+)")
re_var_d = re.compile(r"#([\w\.]+)\[([^\]]+)\]")
re_var_link = re.compile(r"#\[([^\]]+)\]\(([^\)]+)\)")
re_link = re.compile(r"@link{([^\|]+)\|([^}]+)}")
re_typename = re.compile(r"[\[\]\*]")

def link_type(typename, pkg):
	global all_index
	name = re_typename.sub("", typename)
	match = [i for i in all_index if i.endswith("%s/%s" % (pkg, name))]
	if match:
		return '<a href="%s/%s.html">%s</a>' % (domain, match[0], typename)
	return typename

def format_content(content, pkg):
	global all_index
	if isinstance(content, list):
		content = "".join(content)

	varm = re_var_link.findall(content)
	if varm:
		for name, url in varm:
			content = content.replace("#[%s](%s)" % (name, url), '<a href="%s">%s</a>' % (url, name))

	varm = re_var_d.findall(content)
	if varm:
		for var, name in varm:
			key = "%s/%s" % (pkg, var) if var.find('.') < 0 else var.replace('.', '/')
			match = [i for i in all_index if i.endswith(key)]
			if len(match) <= 0:
				continue
			content = content.replace("#%s[%s]" % (var, name), '<a href="%s/%s.html">%s</a>' % (domain, match[0], name))

	varm = re_var.findall(content)
	if varm:
		for var in varm:
			key = "%s/%s" % (pkg, var) if var.find('.') < 0 else var.replace('.', '/')
			match = [i for i in all_index if i.endswith(key)]
			if len(match) <= 0:
				continue
			content = content.replace("#" + var, '<a href="%s/%s.html">%s</a>' % (domain, match[0], var))

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
	map  = get_template("map"),
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
			for func in data["func"]:
				filename = "%s.html" % (func["name"])
				func.update(dict(
					pkg = data["pkg"],
					domain = domain,
					format = format_content,
					linktype = link_type,
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
					linktype = link_type,
				))

				if "struct" in typedef and "construct" in typedef["struct"]:
					for construct in typedef["struct"]["construct"]:
						filename = "%s/%s.html" % (typedef["name"], construct["name"])
						construct.update(dict(
							pkg = data["pkg"],
							domain = domain,
							struct = typedef,
							format = format_content,
							linktype = link_type,
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
							linktype = link_type,
						))
						html = template.func(func)
						save_to_base(dirpath + "/" + filename, content, html, "%s.%s" % (typedef["name"], func["name"]))

				html = template.type(typedef)
				save_to_base(dirpath + "/" + type_filename, content, html, typedef["name"])

all_index = None

def format_doci(result_keys, datas):
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
		if key in datas:
			i["package"] = datas[key] is None
		elif key.startswith("/") or len([j for j in datas if j.startswith(key + "/")]) > 0:
			i["package"] = True
		else:
			i["package"] = False
			i["miss"] = True
	childs = []
	result_p = [x['p'] for x in result]
	for c in result:
		if not c["path"]:
			continue
		if starts(c['p'], result_p):
			continue
		childs.append(c)
	child_key = {}
	for c in childs:
		chs = [i for i in result_keys if i.startswith(c['p'] + "/")]
		if len(chs) <= 0:
			continue
		child_key[c['p']] = chs

	new_result = []
	for r in result:
		new_result.append(r)
		if r['p'] in child_key:
			for new_path in child_key[r['p']]:
				index = new_path.rfind('/')
				path = new_path[:index]
				name = new_path[index + 1:]
				new_result.append(dict(name=name, path=path, p=new_path, package=False))

	return template.map2(result=new_result, map=template.map2, domain=domain)

def starts(path, lib):
	for l in lib:
		if l.startswith(path + "/"):
			return True
	return False

def make_content(datas):
	global all_index
	result = {}
	keys = []
	for data in datas:
		path = data["pkg_path"]
		result[path] = None
		if "func" in data:
			for func in data["func"]:
				p = "%s/%s" % (path, func["name"])
				result[p] = func
				keys.append(p)

		if "typedef" in data:
			for typedef in data["typedef"]:
				p = "%s/%s" % (path, typedef["name"])
				result[p] = typedef
				keys.append(p)

				if "struct" in typedef and "construct" in typedef["struct"]:
					for construct in typedef["struct"]["construct"]:
						p = "%s/%s/%s" % (path, typedef["name"], construct["name"])
						result[p] = construct
						keys.append(p)

				if "struct" in typedef and "func" in typedef["struct"]:
					for func in typedef["struct"]["func"]:
						p = "%s/%s/%s" % (path, typedef["name"], func["name"])
						result[p] = func
						keys.append(p)


	all_index = keys
	return format_doci(keys, result)

if __name__ == "__main__":
	if len(sys.argv) <= 1:
		exit("miss file")

	filepath = sys.argv[1]
	filter_regex = None if len(sys.argv) < 3 else sys.argv[2]
	do(filepath, filter_regex)
