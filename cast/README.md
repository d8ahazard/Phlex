## Synopsis

Functions to control a Chromecast with PHP using a reverse engineered Castv2 protocol. Provides ability to control a Chromecast either locally or remotely from a server.

## Code Example

```php
// Create Chromecast object and give IP and Port
$cc = new Chromecast("217.63.63.259","7019");

// Launch the Chromecast App
$cc->launch("87087D10");

// Wait for the application to be ready
$response = "";
while (!preg_match("/Application status is ready/s",$response)) {
        $response = $cc->getCastMessage();
}

// Connect to the Application
$cc->connect();

// Send the URL
$cc->sendMessage("urn:x-cast:com.chrisridings.piccastr","http://distribution.bbb3d.renderfarming.net/video/mp4/bbb_sunflower_1080p_30fps_normal.mp4");

// Keep the connection alive with heartbeat
while (1==1) {
        $cc->pingpong();
	sleep(10);
}
```

## NOTES

Because this project is primarily intended to run from servers to a remote Chromecast on a different network (e.g. your TV at home), there's no discovery. You need to know the IP of your Chromecast (Chromecasts use mdns for announcement so you can use dns-sd to find it if you don't know). The default port a Chromecast uses is 8009.

If sending content to your home Chromecast from an internet server, you will probably need to enable port forwarding on your router. In which case, use the IP your ISP has assigned you and the port you've chosen to forward.

## TODO

This is only the functional beginnings of this project. For example: notable things yet to do are:

1. Handle binary payloads when encoding to protobuf
2. Protobuf decoding to message objects
3. Handle ping/pings properly

Feel free to help out!
