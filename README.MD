Requirements:
   -Libevent library & Pecl extension.
   -Pthreads.
   -Ansicon(windows).
Google chrome delays in sending the handshake header, hence reading from the socket in non-block mode returns an empty string.The solution to this is adding new connections with an apparently empty header to a list an "unauthorized" array of buffers with a max 5 seconds to send the header, failure to which the event buffer is freed and the connection is closed.