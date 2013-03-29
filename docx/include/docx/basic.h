#ifndef DOCX_BASIC_H
#define DOCX_BASIC_H

// -------------------------------------------------------------------------

#define TPL_USE_AUTO_ALLOC

#ifndef TPL_REGEXP_H
#include "../../../tpl/include/tpl/RegExp.h"
#endif

#ifndef STDEXT_FILEBUF_H
#include "../../../stdext/include/stdext/FileBuf.h"
#endif

namespace docx {

// -------------------------------------------------------------------------

struct Error {
	const char* desc;
	int code;
};

inline Error OK()
{
	Error err = {0, 0};
	return err;
}

inline Error NewError(int code, const char* desc)
{
	Error err = {desc, code};
	return err;
}

// -------------------------------------------------------------------------

typedef NS_STDEXT::Log<NS_STDEXT::FILEStorage> Log;
typedef NS_STDEXT::String Source;

typedef void (*FnParse)(Log& log, Source source);

// -------------------------------------------------------------------------

inline Error ParseFile(FnParse parse, Log& log, const char* inFile)
{
	NS_STDEXT::FileBuf inbuf(inFile);
	if (!inbuf.good()) {
		return NewError(501, "open input file failed");
	}

	Source source(inbuf.begin(), inbuf.end());
	parse(log, source);
	return OK();
}

// -------------------------------------------------------------------------

} // namespace docx

#endif /* DOCX_BASIC_H */

