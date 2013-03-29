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
			"byte", "uintptr"
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
} notBuiltType;

// -------------------------------------------------------------------------

inline void GolangParse(Log& log, Source source)
{
	using namespace tpl;

	typedef DOM<> dom;

	dom::NodeMark tagDecls("decls", true);

	dom::Mark tagComment("comment");
	dom::Mark tagPara("p");
	dom::Mark tagNewline("nl");

	dom::NodeMark tagFunc("func");
		dom::NodeMark tagRecvr("recvr");
			//dom::Mark tagName("name");
			//dom::NodeMark tagType("type");
		dom::Mark tagName("name");
		dom::NodeMark tagArgs("args", true);
			dom::NodeMark tagItem("item");
				//dom::Mark tagName("name");
				dom::NodeMark tagType("type");
					dom::Mark tagPointer("ptr");
					dom::Mark tagNamespace("ns");
					//dom::Mark tagName("name");
		dom::NodeMark tagReturns("returns", true);
			//dom::NodeMark tagItem

	dom::Allocator alloc;
	dom::Document doc(alloc);

	impl::Grammar typ = *gr('*'/tagPointer) + !(gr(c_symbol()/tagNamespace) + '.') + c_symbol()/tagName;

	impl::Grammar recvr = gr('(') + lstart_symbol()/tagName + typ/tagType + ')';

	impl::Grammar arg = (lstart_symbol()/meet(notBuiltType)/tagName + !(typ/tagType)) | typ/tagType;

	impl::Grammar ret = ('(' + (arg/tagItem % ',') + ')') | typ/tagType;

	impl::MarkedRule func =
		c_symbol()/eq("func") + cpp_skip_
		[
			!(recvr/tagRecvr) + ustart_symbol()/tagName + '(' + !((arg/tagItem % ',')/tagArgs) + ')' + !(ret/tagReturns)
		];

	source >> *(
			func/tagFunc/tagDecls + find_eol() |
			cpp_comment<false>()/tagComment/tagDecls + strict_eol() |
			paragraph() + eol() |
			strict_eol()/tagNewline/tagDecls
		)/doc;

	json_print(alloc, log, doc);
}

// -------------------------------------------------------------------------

} // namespace docx

#endif /* DOCX_GO_H */

