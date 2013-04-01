#define TPL_USE_AUTO_ALLOC
#include "Comment.h"

// -------------------------------------------------------------------------

dom::Mark tagTopicArgs("args", true);
dom::Mark tagTopicBrief("brief", true);

dom::Mark tagCategory("category");
dom::Mark tagNamespace("ns");
dom::Mark tagInclude("include");
dom::Mark tagTopicType("type");
dom::Mark tagText("text");
dom::Mark tagUnknown("unknown");
dom::Mark tagSummary("summary");
dom::Mark tagCaption("caption");
dom::Mark tagAttr("attr");

dom::NodeMark tagVals("vals", true);
dom::NodeMark tagDescs("descriptions", true);
dom::NodeMark tagSees("sees", true);
dom::NodeMark tagSeeTopics("topics", true);

dom::NodeMark tagCommentDoc("comment");
dom::NodeMark tagTopic("topic");
dom::NodeMark tagBrief("brief", true); // extend text
dom::NodeMark tagTable("table");
dom::NodeMark tagBody("body", true); // extend text
dom::NodeMark tagReturn("return", true); // extend text
dom::NodeMark tagRemark("remark", true); // extend text

impl::MarkedRule rComment(alloc, comment);

// -------------------------------------------------------------------------
