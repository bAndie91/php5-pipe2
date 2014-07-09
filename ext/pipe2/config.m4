PHP_ARG_ENABLE(pipe2, whether to enable pipe(2) support,
 [ --enable-pipe2   Enable pipe(2) support])


if test "$PHP_PIPE2" = "yes"; then
   AC_DEFINE(HAVE_PIPE2, 1, [Whether you have pipe2])
   PHP_NEW_EXTENSION(pipe2, pipe2.c, $ext_shared)
 fi
