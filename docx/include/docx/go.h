#ifndef DOCX_GO_H
#define DOCX_GO_H

#include <set>

#ifndef DOCX_BASIC_H
#include "basic.h"
#endif

#ifndef TPL_C_LEX_H
#include "../../../tpl/include/tpl/c/Lex.h"
#endif

#define TR info("INFO: %s\n")
#define TRN info("NAME: %s\n")

namespace docx {

// -------------------------------------------------------------------------

class NotBuiltinType
{
	typedef std::set<std::string> Set;

	static const Set& get() {
		static Set g_set;
		static const char* g_types[] = {
			"string", "float64", "float32",
			"int", "int8", "int16", "int32", "int64",
			"uint", "uint8", "uint16", "uint32", "uint64",
			"byte", "uintptr", "struct", "interface", "func"
		};
		for (int i = 0; i < countof(g_types); i++) {
			g_set.insert(g_types[i]);
		}
		return g_set;
	}

public:
	bool TPL_CALL operator()(const String& val) const {
		static const Set& g_set = get();
		const std::string v(val.begin(), val.end());
		return g_set.find(v) == g_set.end();
	}
} notBuiltinType;

class NotKeyword
{
	typedef std::set<std::string> Set;

	static const Set& get() {
		static Set g_set;
		static const char* g_types[] = {
			"struct", "interface", "func"
		};
		for (int i = 0; i < countof(g_types); i++) {
			g_set.insert(g_types[i]);
		}
		return g_set;
	}

public:
	bool TPL_CALL operator()(const String& val) const {
		static const Set& g_set = get();
		const std::string v(val.begin(), val.end());
		return g_set.find(v) == g_set.end();
	}
} notKeyword;

// -------------------------------------------------------------------------

inline void GolangParse(Log& log, Source source)
{
	using namespace tpl;

	typedef DOM<> dom;

	dom::NodeMark tagDecls("decls", true);

	dom::Mark tagComment("comment");
	dom::Mark tagPara("p");
	dom::Mark tagNewline("nl");

	dom::NodeMark tagImport("import");
		//dom::Mark tagName("name");
		dom::Mark tagPkg("pkg");

	dom::NodeMark tagTypedef("typedef");
		//dom::Mark tagName("name");
		//dom::NodeMark tagType("type");

	dom::NodeMark tagFunc("func");
		dom::NodeMark tagRecvr("recvr");
			//dom::Mark tagName("name");
			//dom::NodeMark tagType("type");
		dom::Mark tagName("name");
		dom::NodeMark tagArgs("args", true);
			//dom::Mark tagName("name");
			dom::NodeMark tagType("type");
				dom::Mark tagArray("array");
				dom::Mark tagPointer("ptr");
				//dom::NodeMark tagType("type");
				dom::NodeMark tagTyperef("typeref");
					dom::Mark tagNamespace("ns");
					//dom::Mark tagName("name");
				dom::NodeMark tagStruc("struct");
					dom::NodeMark tagMembers("members", true);
						//dom::Mark tagName("name");
						//dom::NodeMark tagType("type");
		dom::NodeMark tagReturns("returns", true);
			//dom::Mark tagName("name");
			//dom::NodeMark tagType("type");

	dom::Allocator alloc;
	dom::Document doc(alloc);

	impl::Grammar::Var typ;

	impl::Grammar typref = !(gr(c_symbol()/tagNamespace) + '.') + c_symbol()/meet(notKeyword)/tagName;

	impl::Grammar exptyp = *gr('*'/tagPointer) + c_symbol()/meet(notBuiltinType)/tagName;

	impl::Grammar recvr = gr('(') + !gr(lstart_symbol()/tagName) + exptyp/tagType + ')';

	impl::Grammar arg = (lstart_symbol()/meet(notBuiltinType)/tagName + !(typ/tagType)) | typ/tagType;

	impl::Grammar ret = ('(' + (arg/tagReturns % ',') + ')') | typ/tagType/tagReturns;

	impl::Grammar strucline = arg/tagMembers % ',';

	impl::Grammar struc = c_symbol()/eq("struct") + gr('{') + !(eol() % strucline) + '}';

	impl::Grammar import =
		!gr(('.' | c_symbol())/tagName) + c_string()/tagPkg;

	typ =
		(
			'*'/tagPointer + typ/tagType |
			gr(('[' + find<true>(']'))/tagArray) + typ/tagType |
			struc/tagStruc |
			typref/tagTyperef
		);

	impl::MarkedRule imports = c_symbol()/eq("import") + c_pp_skip_
		[
			'(' + !(eol() % (import/tagImport/tagDecls)) + ')' |
			import/tagImport/tagDecls
		];

	impl::MarkedRule typdef =
		c_symbol()/eq("type") + c_pp_skip_[
			c_symbol()/tagName + typ
		];

	impl::MarkedRule func =
		c_symbol()/eq("func") + cpp_skip_
		[
			!(recvr/tagRecvr) + ustart_symbol()/tagName + '(' + !(arg/tagArgs % ',') + ')' + !ret
		];

	source >> *(
			func/tagFunc/tagDecls + find_eol() |
			typdef/tagTypedef/tagDecls + find_eol() |
			imports + find_eol() |
			cpp_comment<false>()/tagComment/tagDecls + strict_eol() |
			paragraph() + eol() |
			strict_eol()/tagNewline/tagDecls
		)/doc;

	json_print(alloc, log, doc);
}

// -------------------------------------------------------------------------

} // namespace docx

#endif /* DOCX_GO_H */

