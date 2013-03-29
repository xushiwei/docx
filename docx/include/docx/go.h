#ifndef DOCX_GO_H
#define DOCX_GO_H

#ifndef DOCX_BASIC_H
#include "basic.h"
#endif

#ifndef TPL_C_LEX_H
#include "../../../tpl/include/tpl/c/Lex.h"
#endif

namespace docx {

// -------------------------------------------------------------------------

inline void GolangParse(Log& log, Source source)
{
	using namespace tpl;

	typedef DOM<> dom;

	dom::Mark tagName("name");
	dom::NodeMark tagBase("base", true);
		dom::Mark tagAccess("access");
		//dom::Mark tagName("name");

	dom::Allocator alloc;
	dom::Document doc(alloc);

	source >> cpp_skip_
		[
			gr(c_symbol()/eq("class")) + c_symbol()/tagName +
			!(':' +
				(
					!gr(c_symbol()/eq("public")/tagAccess) +
					c_symbol()/tagName
				)/tagBase % ','
			) +
			'{' + '}' + ';'
		]/doc;

	json_print(alloc, log, doc);
}

// -------------------------------------------------------------------------

} // namespace docx

#endif /* DOCX_GO_H */

