/* -------------------------------------------------------------------------
// CERL: C++ Erlang Server Model
// 
// Module: cerl/Lex.h
// Creator: xushiwei
// Email: xushiweizh@gmail.com
// Date: 2009-3-26 19:41:58
// 
// $Id: Lex.h 2419 2010-04-08 14:00:42Z scm $
// -----------------------------------------------------------------------*/
#ifndef CERL_LEX_H
#define CERL_LEX_H

#ifndef TPL_C_LEX_H
#include <tpl/c/Lex.h>
#endif

#ifndef TPL_REGEXP_H
#include <tpl/RegExp.h>
#endif

// =========================================================================

using namespace tpl;

typedef impl::Result dom;

extern dom::Document doc;
	extern dom::NodeMark tagSentences;
		extern dom::NodeMark tagStruct;
			extern dom::Mark tagName;
			extern dom::NodeMark tagMembers;
				extern dom::NodeMark tagVar;
					extern dom::Mark tagId;
					extern dom::Mark tagOptional;
					extern dom::Mark tagType;
					extern dom::Mark tagName;
					extern dom::NodeMark tagArray;
					extern dom::Mark tagDefVal;
				extern dom::NodeMark tagSwitch;
					extern dom::Mark tagExpr;
					extern dom::NodeMark tagCases;
						extern dom::Mark tagCondition;
						extern dom::NodeMark tagMembers;

extern impl::Allocator alloc;
extern impl::MarkedGrammar rComposition;

// -------------------------------------------------------------------------
// struct

#define TR	TPL_INFO("TRACE")

#define proto_id()						(gr("id") + '=' + c_integer()/tagId)
#define proto_struct_member_opt()		gr("optional"/tagOptional)
#define proto_struct_member_attrs()		('[' + proto_id() + !(',' + proto_struct_member_opt()) + ']')
#define proto_struct_member_var1()		(gr(c_symbol()/tagType) + c_symbol()/tagName)
#define proto_struct_member_array()		((gr('[') + gr(']'))/tagArray)
#define proto_struct_member_defval()	gr('=' + find(';')/tagDefVal)
#define proto_struct_member_var()		(proto_struct_member_var1() + !proto_struct_member_array() + !proto_struct_member_defval() + ';')
#define proto_struct_member_var_ex()	((proto_struct_member_attrs() + proto_struct_member_var())/tagVar)

#define proto_composition()				(gr('{') + *(proto_struct_member()/tagMembers) + '}')

#define proto_case_keyword()			gr(c_symbol()/eq("case"))
#define proto_of_keyword()				gr(c_symbol()/eq("of"))
#define proto_end_keyword()				gr(c_symbol()/eq("end"))

#define proto_switch_expr()				gr(c_symbol()/tagExpr)
#define proto_switch_case_cond()		gr(c_integer()/tagCondition)
#define proto_switch_case()				(proto_switch_case_cond() + ':' + ref(rComposition))
#define proto_switch_head()				(proto_case_keyword() + proto_switch_expr() + proto_of_keyword())
#define proto_switch()					((proto_switch_head() + *(proto_switch_case()/tagCases) + proto_end_keyword() + ';')/tagSwitch)

#define proto_struct_attrs()			('[' + proto_id() + ']')
#define proto_struct_keyword()			gr(c_symbol()/eq("struct"))
#define proto_struct_head()				(proto_struct_attrs() + proto_struct_keyword() + c_symbol()/tagName)
#define proto_struct_member()			(proto_struct_member_var_ex() | proto_switch())
#define proto_struct()					((proto_struct_head() + rComposition)/tagStruct)

// -------------------------------------------------------------------------
// doc

#define proto_doc()						(skipws_[ *(proto_struct()/tagSentences) ]/doc)

// =========================================================================
// $Log: $

#endif /* CERL_LEX_H */
