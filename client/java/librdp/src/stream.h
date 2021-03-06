/**
 * FreeRDP: A Remote Desktop Protocol Implementation
 * Stream Utils
 *
 * Copyright 2009-2011 Jay Sorg
 * Copyright 2011 Vic Lee
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *	 http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

#ifndef FREERDP_UTILS_STREAM_H
#define FREERDP_UTILS_STREAM_H

#include <string.h>
#include "api.h"
#include "types.h"

struct _STREAM
{
	int size;
	BYTE* p;
	BYTE* data;
};
typedef struct _STREAM STREAM;

#ifdef __cplusplus
extern "C" {
#endif

FREERDP_API STREAM* stream_new(int size);
FREERDP_API void stream_free(STREAM* stream);

#define stream_attach(_s, _buf, _size) do { \
	_s->size = _size; \
	_s->data = _buf; \
	_s->p = _buf; } while (0)
#define stream_detach(_s) memset(_s, 0, sizeof(STREAM))
#define stream_clear(_s) memset(_s->data, 0, _s->size)

FREERDP_API void stream_extend(STREAM* stream, int request_size);
#define stream_check_size(_s, _n) \
	while (_s->p - _s->data + (_n) > _s->size) \
		stream_extend(_s, _n)

#define stream_get_pos(_s) (_s->p - _s->data)
#define stream_set_pos(_s,_m) _s->p = _s->data + (_m)
#define stream_seek(_s,_offset) _s->p += (_offset)
#define stream_rewind(_s,_offset) _s->p -= (_offset)
#define stream_seal(_s) _s->size = (_s->p - _s->data)
#define stream_get_mark(_s,_mark) _mark = _s->p
#define stream_set_mark(_s,_mark) _s->p = _mark
#define stream_get_head(_s) _s->data
#define stream_get_tail(_s) _s->p
#define stream_get_length(_s) (_s->p - _s->data)
#define stream_get_data(_s) (_s->data)
#define stream_get_size(_s) (_s->size)
#define stream_get_left(_s) (_s->size - (_s->p - _s->data))

#define stream_read_BYTE(_s, _v) do { _v = *_s->p++; } while (0)
#define stream_read_UINT16(_s, _v) do { _v = \
	(UINT16)(*_s->p) + \
	(UINT16)(((UINT16)(*(_s->p + 1))) << 8); \
	_s->p += 2; } while (0)
#define stream_read_UINT32(_s, _v) do { _v = \
	(UINT32)(*_s->p) + \
	(((UINT32)(*(_s->p + 1))) << 8) + \
	(((UINT32)(*(_s->p + 2))) << 16) + \
	(((UINT32)(*(_s->p + 3))) << 24); \
	_s->p += 4; } while (0)
#define stream_read_UINT64(_s, _v) do { _v = \
	(UINT64)(*_s->p) + \
	(((UINT64)(*(_s->p + 1))) << 8) + \
	(((UINT64)(*(_s->p + 2))) << 16) + \
	(((UINT64)(*(_s->p + 3))) << 24) + \
	(((UINT64)(*(_s->p + 4))) << 32) + \
	(((UINT64)(*(_s->p + 5))) << 40) + \
	(((UINT64)(*(_s->p + 6))) << 48) + \
	(((UINT64)(*(_s->p + 7))) << 56); \
	_s->p += 8; } while (0)
#define stream_read(_s, _b, _n) do { \
	memcpy(_b, (_s->p), (_n)); \
	_s->p += (_n); \
	} while (0)

#define stream_write_BYTE(_s, _v) do { \
	*_s->p++ = (BYTE)(_v); } while (0)
#define stream_write_UINT16(_s, _v) do { \
	*_s->p++ = (_v) & 0xFF; \
	*_s->p++ = ((_v) >> 8) & 0xFF; } while (0)
#define stream_write_UINT32(_s, _v) do { \
	*_s->p++ = (_v) & 0xFF; \
	*_s->p++ = ((_v) >> 8) & 0xFF; \
	*_s->p++ = ((_v) >> 16) & 0xFF; \
	*_s->p++ = ((_v) >> 24) & 0xFF; } while (0)
#define stream_write_UINT64(_s, _v) do { \
	*_s->p++ = (UINT64)(_v) & 0xFF; \
	*_s->p++ = ((UINT64)(_v) >> 8) & 0xFF; \
	*_s->p++ = ((UINT64)(_v) >> 16) & 0xFF; \
	*_s->p++ = ((UINT64)(_v) >> 24) & 0xFF; \
	*_s->p++ = ((UINT64)(_v) >> 32) & 0xFF; \
	*_s->p++ = ((UINT64)(_v) >> 40) & 0xFF; \
	*_s->p++ = ((UINT64)(_v) >> 48) & 0xFF; \
	*_s->p++ = ((UINT64)(_v) >> 56) & 0xFF; } while (0)
#define stream_write(_s, _b, _n) do { \
	memcpy(_s->p, (_b), (_n)); \
	_s->p += (_n); \
	} while (0)
#define stream_write_zero(_s, _n) do { \
	memset(_s->p, '\0', (_n)); \
	_s->p += (_n); \
	} while (0)
#define stream_set_byte(_s, _v, _n) do { \
	memset(_s->p, _v, (_n)); \
	_s->p += (_n); \
	} while (0)

#define stream_peek_BYTE(_s, _v) do { _v = *_s->p; } while (0)
#define stream_peek_UINT16(_s, _v) do { _v = \
	(UINT16)(*_s->p) + \
	(((UINT16)(*(_s->p + 1))) << 8); \
	} while (0)
#define stream_peek_UINT32(_s, _v) do { _v = \
	(UINT32)(*_s->p) + \
	(((UINT32)(*(_s->p + 1))) << 8) + \
	(((UINT32)(*(_s->p + 2))) << 16) + \
	(((UINT32)(*(_s->p + 3))) << 24); \
	} while (0)
#define stream_peek_UINT64(_s, _v) do { _v = \
	(UINT64)(*_s->p) + \
	(((UINT64)(*(_s->p + 1))) << 8) + \
	(((UINT64)(*(_s->p + 2))) << 16) + \
	(((UINT64)(*(_s->p + 3))) << 24) + \
	(((UINT64)(*(_s->p + 4))) << 32) + \
	(((UINT64)(*(_s->p + 5))) << 40) + \
	(((UINT64)(*(_s->p + 6))) << 48) + \
	(((UINT64)(*(_s->p + 7))) << 56); \
	} while (0)

#define stream_seek_BYTE(_s)	stream_seek(_s, 1)
#define stream_seek_UINT16(_s)	stream_seek(_s, 2)
#define stream_seek_UINT32(_s)	stream_seek(_s, 4)
#define stream_seek_UINT64(_s)	stream_seek(_s, 8)

#define stream_read_UINT16_be(_s, _v) do { _v = \
	(((UINT16)(*_s->p)) << 8) + \
	(UINT16)(*(_s->p + 1)); \
	_s->p += 2; } while (0)
#define stream_read_UINT32_be(_s, _v) do { _v = \
	(((UINT32)(*(_s->p))) << 24) + \
	(((UINT32)(*(_s->p + 1))) << 16) + \
	(((UINT32)(*(_s->p + 2))) << 8) + \
	(((UINT32)(*(_s->p + 3)))); \
	_s->p += 4; } while (0)

#define stream_write_UINT16_be(_s, _v) do { \
	*_s->p++ = ((_v) >> 8) & 0xFF; \
	*_s->p++ = (_v) & 0xFF; } while (0)
#define stream_write_UINT32_be(_s, _v) do { \
	stream_write_UINT16_be(_s, ((_v) >> 16 & 0xFFFF)); \
	stream_write_UINT16_be(_s, ((_v) & 0xFFFF)); \
	} while (0)

#define stream_copy(_dst, _src, _n) do { \
	memcpy(_dst->p, _src->p, _n); \
	_dst->p += _n; \
	_src->p += _n; \
	} while (0)

static INLINE BOOL stream_skip(STREAM* s, int sz) {
	if (stream_get_left(s) < sz)
		return FALSE;
	stream_seek(s, sz);
	return TRUE;
}

#ifdef __cplusplus
}
#endif

#endif /* FREERDP_UTILS_STREAM_H */

