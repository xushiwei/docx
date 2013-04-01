#define TPL_USE_AUTO_ALLOC
#include "Lex.h"
#include "Lex.inl"
#include <stdext/FileBuf.h>
#include <stdext/text/Encoding.h>

// -------------------------------------------------------------------------

int main(int argc, const char* argv[])
{
	using namespace NS_STDEXT;

	ErrorLog err;
	if (argc < 2) {
		err.print("Usage: cpp2json <cpp_file>\n");
		return -1;
	}

	FileBuf file(argv[1]);
	if (!file.good()) {
		err.print(">>> ERROR: open input file failed!\n");
		return -2;
	}
	
	if (file == document)
	{
		OutputLog log;
		codepage_t sourcecp = encoding.empty() ? cp_auto : getEncoding(encoding);
		if (sourcecp == cp_unknown)
		{
			err.print(" >>> ERROR: invalid encoding: ").
				printString(encoding.begin(), encoding.end()).newline();
			return -4;
		}
		
		json_print(alloc, log, doc, sourcecp);
		return 0;
	}

	err.print(" >>> ERROR: invalid file format!\n");
	return -3;
}

// -------------------------------------------------------------------------
