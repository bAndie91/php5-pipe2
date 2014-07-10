#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_pipe2.h"
#include "phpapi.h"
#include "../../main/streams/php_streams_int.h"


static zend_function_entry pipe2_functions[] = {
     PHP_FE(posix_pipe, NULL)
     PHP_FE(posix_dup2, NULL)
     {NULL, NULL, NULL}
};

zend_module_entry pipe2_module_entry = {
#if ZEND_MODULE_API_NO >= 20010901
     STANDARD_MODULE_HEADER,
#endif
     PHP_PIPE2_EXTNAME,
     pipe2_functions,
     NULL,
     NULL,
     NULL,
     NULL,
     NULL,
#if ZEND_MODULE_API_NO >= 20010901
     PHP_PIPE2_VERSION,
#endif
     STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_PIPE2
ZEND_GET_MODULE(pipe2)
#endif



static php_stream *pipe2_stream_from_fd(int fd, const char *mode)
{
	php_stdio_stream_data *self;
	php_stream *stream;

	self = emalloc_rel_orig(sizeof(*self));
	memset(self, 0, sizeof(*self));
	self->file = NULL;
	self->is_pipe = 1;
	self->lock_flag = /* LOCK_UN */ 8;
	self->is_process_pipe = 0;
	self->temp_file_name = NULL;
	self->fd = fd;

	stream = php_stream_alloc_rel(&php_stream_stdio_ops, self, NULL, mode);
	stream->flags |= PHP_STREAM_FLAG_NO_SEEK;
	return stream;
}


PHP_FUNCTION(posix_pipe)
{
	int pipefd[2];
	char *buf;

	php_stream *stream_reader;
	php_stream *stream_writer;
	php_stdio_stream_data *abstract_stream;


	if(pipe(pipefd) == 0)
	{
		stream_reader = pipe2_stream_from_fd(pipefd[0], "rb");
		stream_writer = pipe2_stream_from_fd(pipefd[1], "w");

		array_init(return_value);
		add_next_index_resource(return_value, php_stream_get_resource_id(stream_reader));
		add_next_index_resource(return_value, php_stream_get_resource_id(stream_writer));
		add_next_index_long(return_value, pipefd[0]);
		add_next_index_long(return_value, pipefd[1]);
	}
	else
	{
		RETURN_FALSE;
	}
}

PHP_FUNCTION(posix_dup2)
{
	zval *arg1;
	zval *arg2;
	int fd_old;
	int fd_new;
	php_stream *stream_old;
	php_stream *stream_new;
	
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "zz", &arg1, &arg2) == SUCCESS)
	{
		if(Z_TYPE_P(arg1) == IS_LONG)
		{
			fd_old = Z_LVAL_P(arg1);
		}
		else if(Z_TYPE_P(arg1) == IS_RESOURCE)
		{
			php_stream_from_zval(stream_old, &arg1);
			fd_old = ((php_stdio_stream_data*)stream_old->abstract)->fd;
		}
		else goto invalid_args;
		
		if(Z_TYPE_P(arg2) == IS_LONG)
		{
			fd_new = Z_LVAL_P(arg2);
		}
		else if(Z_TYPE_P(arg2) == IS_RESOURCE)
		{
			php_stream_from_zval(stream_new, &arg2);
			fd_new = ((php_stdio_stream_data*)stream_new->abstract)->fd;
		}
		else goto invalid_args;
		

		if(dup2(fd_old, fd_new) == -1)
		{
			// TODO: errno
			RETURN_FALSE;
		}
		
		RETURN_TRUE;
	}

	invalid_args:
	php_error_docref(NULL TSRMLS_CC, E_WARNING, "Integers or stream type resources needed.");
	RETURN_NULL();
}


