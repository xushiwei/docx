#!/usr/bin/env python
# -*- coding: utf-8 -*-

import sys
import os
import re

re_md_gist = re.compile(r"@gist\(([^\)]+)\)")
re_strip = re.compile(r"^\n*(.*)\n\t*$", re.S)
re_indent = re.compile(r"^(\t*)\w")
re_gist_comment_c_start = re.compile(r"\/\*\s*@gist\s*(.+?)\s*\*/", re.S)
re_gist_comment_c_end = re.compile(r"\/\* @endgist \*\/", re.S)
# re_gist_comment_bash_start = re.compile()
# re_gist_comment_bash_end = re.compile()
# re_gist_comment_cpp_start = re.compile()
# re_gist_comment_cpp_end = re.compile()
cpath = sys.path[0]

def openfile(path):
	if not os.path.exists(path):
		return None
	f = open(path, "r")
	body = f.read()
	f.close()
	return body

def get_gist_block(path):
	gists = dict()
	body = openfile(path)
	if body is None:
		return gists
	start = 0
	while True:
		a = search_one_block(body[start:])
		if a is None:
			break
		name, content, new_start = a
		start += new_start
		if not name in gists:
			gists[name] = content
		else:
			gists[name].extend(["", "...", ""])
			gists[name].extend(content)
	return gists

def search_one_block(body):
	a = re_gist_comment_c_start.search(body)
	if a is None:
		return None
	start = a.span()[1]
	b = re_gist_comment_c_end.search(body[start:])
	if b is None:
		return None
	body = body[start: b.span()[0]+start]
	body = re_strip.sub("\\1", body)
	start_indent = len(re_indent.findall(body)[0])
	body = [i[start_indent:] for i in body.split("\n")]
	return a.group(1), body, b.span()[1] + start

def dirname(path):
	name = os.path.dirname(path)
	if name == "":
		name = "."
	return name

if __name__ == "__main__":
	if len(sys.argv) <= 1:
		sys.stderr.write("Usage: %s GistFile > OutputFile\n" % os.path.basename(sys.argv[0]))
		exit(2)

	body = openfile(sys.argv[1])
	if body is None:
		sys.stderr.write("Not such File.")
		exit(2)
		
	rpath = dirname(sys.argv[1])
	result = []
	files = []
	for i in re_md_gist.findall(body):
		file_path = i
		if i.find("#") > 0:
			file_path = file_path.split("#")[0]
		files.append("%s/%s" % (rpath, file_path))
		result.append(i)
	files = list(set(files))
	gists = {}
	for f in files:
		blocks = get_gist_block(f)
		for block_key in blocks:
			gists["%s#%s" % (f, block_key)] = blocks[block_key]
	
	errors = []
	for i in result:
		key = "%s/%s" % (rpath, i)
		if key in gists:
			# print re_md_gist.search(body).group()
			s = re_md_gist.search(body).span()[0]
			s = body[body[s-50: s].rfind("\n")+s-50+1: s]
			content = "\n\n" + s + ("\n%s" % s).join(gists[key])
			content = content.replace("\\", "\\\\")
			
			body = re.sub(r"\s*@gist\s*\(%s\)" % i, content, body)
		else:
			errors.append(i)

	if len(errors) > 0:
		sys.stderr.write("error: No Such File or Author\n")
		for i, error in enumerate(errors):
			sys.stderr.write("%s: %s\n" % (i+1, error))
		exit(2)
	print body
	


