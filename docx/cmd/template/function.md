## $name
{%arg_string=[]}
{if isset('args')}
	{for a in args}
		{%arg_string.append('%s %s' % (a['name'], a['type']['display_name']))}
	{end}
{end}
{%arg_string=', '.join(arg_string)}
{if isset('doc')}

\t$doc['brief']

{end}
### 代码
```{go}
func $name($arg_string) {}
```

{if isset('args')}
### 参数
{for i in args}
	###### $i['name'] 类型: $i['type']['display_name']
	{if 'doc' in i}
	
	\t$i['doc']
	
	{end}
{end}
{end}

{if isset('returns')}
### 返回值
{for i in returns}
	{if 'name' in i}
		###### $i['name'] 类型: $i['type']['display_name']
	{else}
		###### 类型: $i['type']['display_name']
	{end}
	{if 'doc' in i}
	
		\t$i['doc']
	
	{end}
{end}
{end}
