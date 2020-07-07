<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width">
    <title>Peer-to-Peer Cue System --- Reciever</title>
    <link rel="stylesheet" href="{{asset('assets/video')}}/style.css">
</head>
<body>
<table class="display">
    <tr>
        <td class="title">Status:</td>
        <td class="title">Messages:</td>
    </tr>
    <tr>
        <td>
            <div id="receiver-id" style="font-weight: bold;" title="Copy this ID to the input on send.html.">ID:</div>
        </td>
        <td>
            <input type="text" id="sendMessageBox" placeholder="Enter a message..." autofocus="true" />
            <button type="button" id="sendButton">Send</button>
            <button type="button" id="clearMsgsButton">Clear Msgs (Local)</button>
        </td>
    </tr>
    <tr>
        <td><div id="status" class="status"></div></td>
        <td><div class="message" id="message"></div></td>
    </tr>
    <tr>
        <td class="display-box standby" id="standby"><h2>Standby</h2></td>
        <td class="display-box hidden" id="go"><h2>Go</h2></td>
    </tr>
    <tr>
        <td class="display-box hidden" id="fade"><h2>Fade</h2></td>
        <td class="display-box hidden" id="off"><h2>All Off</h2></td>
    </tr>
    <tr>
        <td>
            <video id="video_div" autoplay playsinline></video>
        </td>
        <td>

        </td>
    </tr>
</table>

{{--<script src="https://cdn.jsdelivr.net/npm/peerjs@1.2.0/dist/peerjs.min.js"></script>--}}
<script src="https://cdn.bootcdn.net/ajax/libs/peerjs/1.2.0/peerjs.js"></script>
<script type="text/javascript">
    (function () {

        var lastPeerId = null;
        var peer = null; // Own peer object
        var peerId = null;
        var conn = null;
        var recvId = document.getElementById("receiver-id");
        var status = document.getElementById("status");
        var message = document.getElementById("message");
        var standbyBox = document.getElementById("standby");
        var goBox = document.getElementById("go");
        var fadeBox = document.getElementById("fade");
        var offBox = document.getElementById("off");
        var sendMessageBox = document.getElementById("sendMessageBox");
        var sendButton = document.getElementById("sendButton");
        var clearMsgsButton = document.getElementById("clearMsgsButton");

        /**
         * Create the Peer object for our end of the connection.
         *
         * Sets up callbacks that handle any events related to our
         * peer object.
         */
        function initialize() {
            // Create own peer object with connection to shared PeerJS server
            peer = new Peer(null, {
                host: 'laravel.test',
                port: 9000,
                debug: 3,
                path: '/myapp'
            });

            peer.on('open', function (id) {
                // Workaround for peer.reconnect deleting previous id
                if (peer.id === null) {
                    console.log('Received null id from peer open');
                    peer.id = lastPeerId;
                } else {
                    lastPeerId = peer.id;
                }

                console.log('ID: ' + peer.id);
                recvId.innerHTML = "ID: " + peer.id;
                status.innerHTML = "Awaiting connection...";
            });
            peer.on('connection', function (c) {
                // Allow only a single connection
                if (conn && conn.open) {
                    c.on('open', function() {
                        c.send("Already connected to another client");
                        setTimeout(function() { c.close(); }, 500);
                    });
                    return;
                }

                conn = c;
                console.log("Connected to: " + conn.peer);
                status.innerHTML = "Connected";
                ready();
            });
            peer.on('disconnected', function () {
                status.innerHTML = "Connection lost. Please reconnect";
                console.log('Connection lost. Please reconnect');

                // Workaround for peer.reconnect deleting previous id
                peer.id = lastPeerId;
                peer._lastServerId = lastPeerId;
                peer.reconnect();
            });
            peer.on('close', function() {
                conn = null;
                status.innerHTML = "Connection destroyed. Please refresh";
                console.log('Connection destroyed');
            });
            peer.on('error', function (err) {
                console.log(err);
                alert('' + err);
            });
            peer.on('call', function (call) {
                var acceptsCall = confirm("Videocall incoming, do you want to accept it ?");

                if(acceptsCall){
                    // Answer the call with your own video/audio stream
                    call.answer(window.localStream);

                    // Receive data
                    call.on('stream', function (stream) {
                        var  video = document.querySelector('video');
                        window.stream = stream;
                        video.srcObject = stream;
                    });

                    // Handle when the call finishes
                    call.on('close', function(){
                        alert("The videocall has finished");
                    });

                    // use call.close() to finish a call
                }else{
                    console.log("Call denied !");
                }
            });

        };

        /**
         * Triggered once a connection has been achieved.
         * Defines callbacks to handle incoming data and connection events.
         */
        function ready() {
            conn.on('data', function (data) {
                console.log("Data recieved");
                var cueString = "<span class=\"cueMsg\">Cue: </span>";
                switch (data) {
                    case 'Go':
                        go();
                        addMessage(cueString + data);
                        break;
                    case 'Fade':
                        fade();
                        addMessage(cueString + data);
                        break;
                    case 'Off':
                        off();
                        addMessage(cueString + data);
                        break;
                    case 'Reset':
                        reset();
                        addMessage(cueString + data);
                        break;
                    default:
                        addMessage("<span class=\"peerMsg\">Peer: </span>" + data);
                        break;
                };
            });
            conn.on('close', function () {
                status.innerHTML = "Connection reset<br>Awaiting connection...";
                conn = null;
            });
        }

        function go() {
            standbyBox.className = "display-box hidden";
            goBox.className = "display-box go";
            fadeBox.className = "display-box hidden";
            offBox.className = "display-box hidden";
            return;
        };

        function fade() {
            standbyBox.className = "display-box hidden";
            goBox.className = "display-box hidden";
            fadeBox.className = "display-box fade";
            offBox.className = "display-box hidden";
            return;
        };

        function off() {
            standbyBox.className = "display-box hidden";
            goBox.className = "display-box hidden";
            fadeBox.className = "display-box hidden";
            offBox.className = "display-box off";
            return;
        }

        function reset() {
            standbyBox.className = "display-box standby";
            goBox.className = "display-box hidden";
            fadeBox.className = "display-box hidden";
            offBox.className = "display-box hidden";
            return;
        };

        function addMessage(msg) {
            var now = new Date();
            var h = now.getHours();
            var m = addZero(now.getMinutes());
            var s = addZero(now.getSeconds());

            if (h > 12)
                h -= 12;
            else if (h === 0)
                h = 12;

            function addZero(t) {
                if (t < 10)
                    t = "0" + t;
                return t;
            };

            message.innerHTML = "<br><span class=\"msg-time\">" + h + ":" + m + ":" + s + "</span>  -  " + msg + message.innerHTML;
        }

        function clearMessages() {
            message.innerHTML = "";
            addMessage("Msgs cleared");
        }

        // Listen for enter in message box
        sendMessageBox.addEventListener('keypress', function (e) {
            var event = e || window.event;
            var char = event.which || event.keyCode;
            if (char == '13')
                sendButton.click();
        });
        // Send message
        sendButton.addEventListener('click', function () {
            if (conn && conn.open) {
                var msg = sendMessageBox.value;
                sendMessageBox.value = "";
                conn.send(msg);
                console.log("Sent: " + msg)
                addMessage("<span class=\"selfMsg\">Self: </span>" + msg);
            } else {
                console.log('Connection is closed');
            }
        });

        // Clear messages box
        clearMsgsButton.addEventListener('click', clearMessages);

        initialize();
    })();
</script>
</body>
</html>
