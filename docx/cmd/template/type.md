## $name
{if isset('doc')}

\t$doc['brief']

{end}

### 代码
```{go}
{if isset('typeref')}
type $name $typeref['name']
{else}
type $name struct {
{if isset('struct') and 'vars' in struct}
{for var in struct['vars']}
	\t$var['display_name']
{end}
{end}
}
{end}
```

{if isset('struct') and 'vars' in struct}
### 字段

{for var in struct['vars']}
	{if 'name' in var}
		###### $var['name'] 类型: $var['type']['display_name']

		{if 'doc' in var}
		\t$var["doc"]
		{end}

	{else}
		(extends) $var['type']['display_name']
	{end}
{end}
{end}

{if isset('struct') and 'func' in struct}
### 函数

{for func in struct['func'].values()}
	{%arg_string=[]}
	{if 'args' in func}
		{for a in func['args']}
			{%arg_string.append('%s %s' % (a['name'], a['type']['display_name']))}
		{end}
	{end}
	{%arg_string=', '.join(arg_string)}
	{%ret_str=[]}
	{if 'returns' in func}
		{for ret in func['returns']}
			{if not 'name' in ret}
				{%ret_str.append(ret['type']['display_name'])}
			{else}
				{%ret_str.append('%s %s' % (ret['name'], ret['type']['display_name']))}
			{end}
		{end}
	{end}
	{%ret_str=', '.join(ret_str)}
	{%ret_str = '(%s)' % ret_str if len(ret_str) > 0 else ''}

	#### $func['name']
	{%recvr = func['recvr']}
	```{go}
	func ($recvr['name'] $recvr['type']['name']) $func['name']($arg_string) $ret_str {}
	```
	{if 'args' in func}
	##### 参数

		{for a in func['args']}
			###### $a['name'] 类型: $a['type']['display_name']
			{if 'doc' in a}
			
			\t$a['doc']
		
			{end}
		{end}
	{end}
	{if 'returns' in func}
	##### 返回值

		{for a in func['returns']}
			{if 'name' in a}
			###### $a['name'] 类型: $a['type']['display_name']
			{else}
			###### 类型: $a['type']['typeref_name']
			{end}
			{if 'doc' in a}
			
			\t$a['doc']
		
			{end}
		{end}
	{end}
	
{end}
{end}
