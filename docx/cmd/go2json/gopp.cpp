#include "../../include/docx/go.h"

using namespace docx;

int main(int argc, const char** argv)
{
	if (argc < 2) {
		printf("Usage: go2json <goFile>\n");
		return 1;
	}

	NS_STDEXT::OutputLog log;
	Error err = ParseFile(GolangParse, log, argv[1]);
	if (err.desc != NULL) {
		fprintf(stderr, "Error(%d): %s\n", err.code, err.desc);
		return err.code;
	}

	return 0;
}

