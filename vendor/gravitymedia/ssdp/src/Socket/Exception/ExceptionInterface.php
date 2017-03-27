<?php
/**
 * This file is part of the Ssdp project
 *
 * @author Daniel Schröder <daniel.schroeder@gravitymedia.de>
 */

namespace GravityMedia\Ssdp\Socket\Exception;

/**
 * Socket exception interface
 *
 * @package GravityMedia\Ssdp\Socket\Exception
 */
interface ExceptionInterface
{
    // http://www.ioplex.com/~miallen/errcmpp.html
    const EPERM = 1; // Operation not permitted
    const ENOENT = 2; // No such file or directory
    const ESRCH = 3; // No such process
    const EINTR = 4; // Interrupted system call
    const EIO = 5; // I/O error
    const ENXIO = 6; // No such device or address
    const E2BIG = 7; // Arg list too long
    const ENOEXEC = 8; // Exec format error
    const EBADF = 9; // Bad file number
    const ECHILD = 10; // No child processes
    const EAGAIN = 11; // Try again
    const ENOMEM = 12; // Out of memory
    const EACCES = 13; // Permission denied
    const EFAULT = 14; // Bad address
    const ENOTBLK = 15; // Block device required
    const EBUSY = 16; // Device or resource busy
    const EEXIST = 17; // File exists
    const EXDEV = 18; // Cross-device link
    const ENODEV = 19; // No such device
    const ENOTDIR = 20; // Not a directory
    const EISDIR = 21; // Is a directory
    const EINVAL = 22; // Invalid argument
    const ENFILE = 23; // File table overflow
    const EMFILE = 24; // Too many open files
    const ENOTTY = 25; // Not a typewriter
    const ETXTBSY = 26; // Text file busy
    const EFBIG = 27; // File too large
    const ENOSPC = 28; // No space left on device
    const ESPIPE = 29; // Illegal seek
    const EROFS = 30; // Read-only file system
    const EMLINK = 31; // Too many links
    const EPIPE = 32; // Broken pipe
    const EDOM = 33; // Math argument out of domain of func
    const ERANGE = 34; // Math result not representable
    const EDEADLK = 35; // Resource deadlock would occur
    const ENAMETOOLONG = 36; // File name too long
    const ENOLCK = 37; // No record locks available
    const ENOSYS = 38; // Function not implemented
    const ENOTEMPTY = 39; // Directory not empty
    const ELOOP = 40; // Too many symbolic links encountered
    const ENOMSG = 42; // No message of desired type
    const EIDRM = 43; // Identifier removed
    const EOVERFLOW = 75; // Value too large for defined data type
    const EILSEQ = 84; // Illegal byte sequence
    const ENOTSOCK = 88; // Socket operation on non-socket
    const EDESTADDRREQ = 89; // Destination address required
    const EMSGSIZE = 90; // Message too long
    const EPROTOTYPE = 91; // Protocol wrong type for socket
    const ENOPROTOOPT = 92; // Protocol not available
    const EPROTONOSUPPORT = 93; // Protocol not supported
    const EOPNOTSUPP = 95; // Operation not supported on transport endpoint
    const EAFNOSUPPORT = 97; // Address family not supported by protocol
    const EADDRINUSE = 98; // Address already in use
    const EADDRNOTAVAIL = 99; // Cannot assign requested address
    const ENETDOWN = 100; // Network is down
    const ENETUNREACH = 101; // Network is unreachable
    const ENETRESET = 102; // Network dropped connection because of reset
    const ECONNABORTED = 103; // Software caused connection abort
    const ECONNRESET = 104; // Connection reset by peer
    const ENOBUFS = 105; // No buffer space available
    const EISCONN = 106; // Transport endpoint is already connected
    const ENOTCONN = 107; // Transport endpoint is not connected
    const ETIMEDOUT = 110; // Connection timed out
    const ECONNREFUSED = 111; // Connection refused
    const EHOSTUNREACH = 113; // No route to host
    const EALREADY = 114; // Operation already in progress
    const EINPROGRESS = 115; // Operation now in progress
    const ESTALE = 116; // Stale NFS file handle
}
