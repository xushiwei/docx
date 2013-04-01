
extern dom::NodeMark tagSentences("sentences", true);
extern dom::NodeMark tagStruct("struct");
extern dom::NodeMark tagMembers("members", true);
extern dom::NodeMark tagVar("var");
extern dom::NodeMark tagArray("array");
extern dom::NodeMark tagSwitch("switch");
extern dom::NodeMark tagCases("cases", true);

extern dom::Mark tagName("name");
extern dom::Mark tagId("id");
extern dom::Mark tagOptional("optional");
extern dom::Mark tagType("type");
extern dom::Mark tagDefVal("defval");
extern dom::Mark tagExpr("expr");
extern dom::Mark tagCondition("condition");

impl::Allocator alloc;
impl::MarkedGrammar rComposition(alloc, proto_composition());

dom::Document doc(alloc);
