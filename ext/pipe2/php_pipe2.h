#ifndef PHP_PIPE2_H
#define PHP_PIPE2_H 1

#define PHP_PIPE2_VERSION "1.0"
#define PHP_PIPE2_EXTNAME "pipe2"

PHP_FUNCTION(posix_pipe);
PHP_FUNCTION(stream_dup2);
PHP_FUNCTION(posix_dup2);

extern zend_module_entry pipe2_module_entry;
#define phpext_pipe2_ptr &pipe2_module_entry

#endif
