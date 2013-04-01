#ifndef sdl_myserver_h
#define sdl_myserver_h

namespace myserver
{
	enum { code_false = 0 };
	enum { code_true = 1 };
	enum { code_ok = 2 };
	enum { code_confliction = 0x81 };
	enum { code_notimpl = 0x82 };
	enum { code_replication = 0x101 };
	enum { code_timeout = 0x102 };
	enum { code_md5 = 0x103 };
	enum { code_error = 0xffff };

	typedef cerl::Array<cerl::Char, 16> MD5;

	typedef cerl::String Name;

	typedef struct {
		cerl::Code _code;
		union {
			struct {
				cerl::UInt32 value;
			} replication;
			struct {
				cerl::UInt32 value;
			} timeout;
			struct {
				MD5 value;
			} md5;
		};
	} Option;

	typedef struct {
		Name name;
		cerl::BasicArray<Option> options;
	} Handle;

	typedef struct {
		cerl::Code _code;
	} Reason;

	typedef struct {
		cerl::Code _code;
	} Information;

	class Foo
	{
		enum { code_open = 1 };
		enum { code_forward = 2 };
		enum { code_put = 3 };
		enum { code_erase = 4 };
		enum { code_clear = 5 };
		enum { code_close = 6 };
		enum { code_info = 7 };
		enum { code_stop = 0x81 };

		typedef struct {
			cerl::Code _code;
			union {
				struct {
					Handle handle;
				};
				struct {
					Reason reason;
				} error;
			};
		} _OpenResult;

		typedef struct {
			cerl::Code _code;
			struct {
				Reason reason;
			} error;
		} _ForwardResult;

		typedef struct {
			cerl::Code _code;
			struct {
				Reason reason;
			} error;
		} _PutResult;

		typedef struct {
			cerl::Code _code;
			struct {
				Reason reason;
			} error;
		} _EraseResult;

		typedef struct {
			cerl::Code _code;
			struct {
				Reason reason;
			} error;
		} _ClearResult;

		typedef struct {
			cerl::Code _code;
			struct {
				Reason reason;
			} error;
		} _CloseResult;

		typedef struct {
			cerl::Code _code;
			union {
				struct {
					Information info;
				};
				struct {
					Reason reason;
				} error;
			};
		} _InfoResult;

		typedef struct {
			cerl::Code _code;
			union {
				struct {
					Handle handle;
					cerl::String key;
					cerl::String data;
				} put;
				struct {
					Handle handle;
					cerl::String key;
				} erase;
				struct {
					Handle handle;
				} clear;
			};
		} Message;

		void open(
			_OpenResult& result,
			Name name,
			cerl::BasicArray<Option> options
		);

		void forward(
			_ForwardResult& result,
			Handle handle,
			Message message
		);

		void put(
			_PutResult& result,
			Handle handle,
			cerl::String key,
			cerl::String data
		);

		void erase(
			_EraseResult& result,
			Handle handle,
			cerl::String key
		);

		void clear(
			_ClearResult& result,
			Handle handle
		);

		void close(
			_CloseResult& result,
			Handle handle
		);

		void info(
			_InfoResult& result
		);

		/*[async]*/ cerl::Code stop(
		);
	};
}

#endif /* sdl_myserver_h */
