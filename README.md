# pipe(2) binding for PHP

It is a PHP extension provides functions for low-level
file descriptor operations:
* ``posix_pipe()`` binds to ``pipe()`` syscall
* ``posix_dup2()`` binds to ``dup2()`` syscall
* ``posix_close()`` binds to ``close()`` syscall

# Have you ever wondered how to do cheap forking and running subprocesses?
PHP already provides ``exec()``, ``system()`` and ``proc_open()`` to
run a child process, but with not as little overhead as possible.
``exec()``, ``system()`` and ``proc_open()`` spawns shell first. 

You can use **pcntl** family to build a low cost exec function. 
*However pcntl is not available in all SAPI.*
You will need ``pcntl_fork()`` and ``pcntl_exec()`` then ``pcntl_waitpid()``
to implement a subprocess executing function.
In this case you lose the child process' output, stderr and the ability
to pipe data into its stdin, which is built in ``proc_open()`` by default 
and partially in ``exec()`` (only stdout is captured).

A possible low-level solution is to request pipes from the OS and
redirecting them in the child process before exec'ing.
This module provides bindings to POSIX API functions suitable for this job.

# Usage
``array posix_pipe()``
Returns an array holding stream resources for the new 
pipe's reader and writer end, and their file descriptors.
```
[0] => stream reader,
[1] => stream writer,
[2] => int reader_fd,
[3] => int writer_fd,
```
``bool posix_dup2(mixed oldfd, mixed newfd)``
oldfd and newfd can be stream type resources or int.
Returns TRUE if success, FALSE otherwise.
``bool posix_close(int fd)``
Returns TRUE if success, FALSE otherwise.

# Example
see example.php
