#ifndef EXAMPLE_H
#define EXAMPLE_H

#ifndef CERL_PROTOBUF_H
#include <cerl/ProtoBuf.h>
#endif

// --------------------------------------------------------------------------------------
// struct Simplest

struct Simplest
{
	/* [id=1] */ cerl::Int32 a;
	/* [id=2] */ cerl::String b;
	/* [id=3, optional] */ cerl::Bool c /* = true */;
	/* [id=4] */ cerl::BasicArray<cerl::UInt16> d;
};

CERL_DEFINE_TYPEID(Simplest, 0x0a01);

// --------------------------------------------------------------------------------------
// struct SelectiveRecord

struct SelectiveRecord
{
	/* [id=1] */ cerl::Int32 type;
	/* [id=2] */ cerl::String path;

	union {
		/* type = 1 */ struct {
			/* [id=101] */ cerl::Int32 a;
			/* [id=102] */ cerl::String b;
		};
		/* type = 2 */ struct {
			/* [id=201] */ cerl::Int32 c;
		};
		/* type = 3 */ struct {
			/* [id=301] */ cerl::BasicArray<cerl::String> array;
		};
	};
};

CERL_DEFINE_TYPEID(SelectiveRecord, 0x0a02);

// --------------------------------------------------------------------------------------

NS_CERL_IO_BEGIN

template <class OutputStreamT>
inline void cerl_call put(OutputStreamT& os, const Simplest& inst)
{
	putBegin(os, CERL_TYPEID(Simplest));
	{
		putObject(os, 1, inst.a);
		putObject(os, 2, inst.b);
		if (inst.c != true)
			putObject(os, 3, inst.c);
		putObject(os, 4, inst.d);
	}
	putEnd(os);
}

template <class OutputStreamT>
inline void cerl_call put(OutputStreamT& os, const SelectiveRecord& inst)
{
	putBegin(os, CERL_TYPEID(SelectiveRecord));
	{
		putObject(os, 1, inst.type);
		putObject(os, 2, inst.path);
		switch (inst.type)
		{
		case 1:
			putObject(os, 101, inst.a);
			putObject(os, 102, inst.b);
			break;
		case 2:
			putObject(os, 201, inst.c);
			break;
		case 3:
			putObject(os, 301, inst.array);
			break;
		}
	}
	putEnd(os);
}

template <class AllocT, class InputStreamT>
inline bool cerl_call get(AllocT& alloc, InputStreamT& is, Simplest& inst)
{
	inst.c = true;
	return
		getBegin(is, CERL_TYPEID(Simplest)) &&
			getObject(is, 1, inst.a) &&
			getObject(alloc, is, 2, inst.b) &&
			getOptional(is, 3, inst.c) &&
			getObject(alloc, is, 4, inst.d) &&
		getEnd(is);
}

template <class AllocT, class InputStreamT>
inline bool cerl_call get(AllocT& alloc, InputStreamT& is, SelectiveRecord& inst)
{
	if (
		!getBegin(is, CERL_TYPEID(SelectiveRecord)) ||
		!getObject(is, 1, inst.type) ||
		!getObject(alloc, is, 2, inst.path)
		)
		return false;

	switch (inst.type)
	{
	case 1:
		if (
			!getObject(is, 101, inst.a) ||
			!getObject(alloc, is, 102, inst.b)
			)
			return false;
		break;
	case 2:
		if (
			!getObject(is, 201, inst.c)
			)
			return false;
		break;
	case 3:
		if (
			!getObject(alloc, is, 301, inst.array)
			)
			return false;
		break;
	}
	return getEnd(is);
}

NS_CERL_IO_END

// --------------------------------------------------------------------------------------

#endif // EXAMPLE_H
