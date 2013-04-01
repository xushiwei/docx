
dom::NodeMark tagSentences("sentences", true);
dom::NodeMark tagBaseClasses("bases", true);
dom::NodeMark tagArgs("args", true);

dom::NodeMark tagTemplate("template");
dom::NodeMark tagClass("class");
dom::NodeMark tagMember("member");
dom::NodeMark tagGlobal("global");
dom::NodeMark tagConstructor("ctor");
dom::NodeMark tagDestructor("dtor");
dom::NodeMark tagTypeCast("typecast");
dom::NodeMark tagTypedef("type");
dom::NodeMark tagEnum("enum");
dom::NodeMark tagMacro("macro");
dom::NodeMark tagMacroArgList("arglist");

dom::Mark tagClassKeyword("keyword");
dom::Mark tagHeader("header");
dom::Mark tagAccess("access");
dom::Mark tagType("type");
dom::Mark tagCallType("calltype");
dom::Mark tagName("name");
dom::Mark tagAttr2("funcattr");
dom::Mark tagDefVal("defval");
dom::Mark tagMacroBody("body");
dom::Mark tagMacroArgs("args", true);

impl::Allocator alloc;
impl::MarkedGrammar rCppSymbol(alloc, cppsymbol);
impl::MarkedGrammar rType(alloc, type_);
impl::MarkedGrammar rClass(alloc, classdef);
String className;
String encoding;

dom::Document doc(alloc);
