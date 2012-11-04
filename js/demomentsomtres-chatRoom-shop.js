// Script adapted by DeMomentSomTres
// for WordPress plugin demomentsomtres-chatRoom.
// more info http://demomentsomtres.com

TB.addEventListener("exception", exceptionHandler);		
if (TB.checkSystemRequirements() != TB.HAS_REQUIREMENTS) {
    alert("You don't have the minimum requirements to run this application."
        + "Please upgrade to the latest version of Flash.");
} else {
    session = TB.initSession(sessionId);	// Initialize session

    // Add event listeners to the session
    session.addEventListener('sessionConnected', sessionConnectedHandler);
    session.addEventListener('sessionDisconnected', sessionDisconnectedHandler);
    session.addEventListener('connectionCreated', connectionCreatedHandler);
    session.addEventListener('connectionDestroyed', connectionDestroyedHandler);
    session.addEventListener('streamCreated', streamCreatedHandler);
    session.addEventListener('streamDestroyed', streamDestroyedHandler);
    jQuery('#connectLink').show();
    jQuery('#disconnectLink').hide();
    jQuery('#publishLink').hide();
    jQuery('#unpublishLink').hide();
    jQuery('#connectLink').click(function(){
        connect()
    });
    jQuery('#disconnectLink').click(function(){
        disconnect()
    });
    jQuery('#publishLink').click(function(){
        startPublishing()
    });
    jQuery('#unpublishLink').click(function(){
        stopPublishing()
    });
}
//--------------------------------------
//  LINK CLICK HANDLERS
//--------------------------------------

/* If testing the app from the desktop, be sure to check the Flash Player Global Security setting
 * to allow the page from communicating with SWF content loaded from the web. For more information,
 * see http://www.tokbox.com/opentok/build/tutorials/helloworld.html#localTest
 */
function connect() {
    var dataToSend={
        type: "GET",
        url: WP_ADMIN_URL,
        dataType: 'html',
        data: 'action=dmst_chatRoom_open',
        success: function(data){
            jQuery('#messages').html(data);
        },
        error: function(data){
            jQuery('#messages').html(data);
        }
    };
    jQuery.ajax(dataToSend);
    jQuery('#myCamera').addClass('dmst_chatRoom_standby');
    jQuery('#myCamera').removeClass('dmst_chatRoom_closed');
    jQuery('#myCamera').removeClass('dmst_chatRoom_open');
    session.connect(apiKey, token);
}
function disconnect() {
    var dataToSend={
        type: "GET",
        url: WP_ADMIN_URL,
        dataType: 'html',
        data: 'action=dmst_chatRoom_close',
        success: function(data){
            jQuery('#messages').html(data);
        },
        error: function(data){
            jQuery('#messages').html(data);
        }
    };
    jQuery.ajax(dataToSend);
    jQuery('#myCamera').addClass('dmst_chatRoom_closed');
    jQuery('#myCamera').removeClass('dmst_chatRoom_standby');
    jQuery('#myCamera').removeClass('dmst_chatRoom_open');
    session.disconnect();
    jQuery('#disconnectLink').hide();
    jQuery('#publishLink').hide();
    jQuery('#unpublishLink').hide();
    jQuery('#connectLink').show();
}

// Called when user wants to start publishing to the session
function startPublishing() {
    if (!publisher) {
        var parentDiv = document.getElementById("myCamera");
        var publisherDiv = document.createElement('div'); // Create a div for the publisher to replace
        publisherDiv.setAttribute('id', 'opentok_publisher');
        parentDiv.appendChild(publisherDiv);
        var publisherProps = {
            width: VIDEO_WIDTH, 
            height: VIDEO_HEIGHT
        };
        publisher = TB.initPublisher(apiKey, publisherDiv.id, publisherProps);  // Pass the replacement div id and properties
        session.publish(publisher);
        jQuery('#myCamera').addClass('dmst_chatRoom_open');
        jQuery('#myCamera').removeClass('dmst_chatRoom_standby');
        jQuery('#myCamera').removeClass('dmst_chatRoom_closed');
        jQuery('#unpublishLink').show();
        jQuery('#publishLink').hide();
    }
}

function stopPublishing() {
    if (publisher) {
        session.unpublish(publisher);
    }
    publisher = null;
    jQuery('#myCamera').addClass('dmst_chatRoom_standby');
    jQuery('#myCamera').removeClass('dmst_chatRoom_open');
    jQuery('#myCamera').removeClass('dmst_chatRoom_closed');
    jQuery('#publishLink').show();
    jQuery('#unpublishLink').hide();
}

//--------------------------------------
//  OPENTOK EVENT HANDLERS
//--------------------------------------

function sessionConnectedHandler(event) {
    // Subscribe to all streams currently in the Session
    for (var i = 0; i < event.streams.length; i++) {
        addStream(event.streams[i]);
    }
    jQuery('#disconnectLink').show();
    jQuery('#publishLink').show();
    jQuery('#connectLink').hide();
}

function streamCreatedHandler(event) {
    // Subscribe to the newly created streams
    for (var i = 0; i < event.streams.length; i++) {
        addStream(event.streams[i]);
    }
}

function streamDestroyedHandler(event) {
// This signals that a stream was destroyed. Any Subscribers will automatically be removed.
// This default behaviour can be prevented using event.preventDefault()
}

function sessionDisconnectedHandler(event) {
    // This signals that the user was disconnected from the Session. Any subscribers and publishers
    // will automatically be removed. This default behaviour can be prevented using event.preventDefault()
    publisher = null;

    jQuery('#connectLink').show();
    jQuery('disconnectLink').hide();
    jQuery('publishLink').hide();
    jQuery('unpublishLink').hide();
}

function connectionDestroyedHandler(event) {
// This signals that connections were destroyed
}

function connectionCreatedHandler(event) {
// This signals new connections have been created.
}

/*
		If you un-comment the call to TB.addEventListener("exception", exceptionHandler) above, OpenTok calls the
		exceptionHandler() method when exception events occur. You can modify this method to further process exception events.
		If you un-comment the call to TB.setLogLevel(), above, OpenTok automatically displays exception event messages.
		*/
function exceptionHandler(event) {
    if (event.code == 1013) {
        document.body.innerHTML = "This page is trying to connect a third client to an OpenTok peer-to-peer session. "
    + "Only two clients can connect to peer-to-peer sessions.";
    } else {
        alert("Exception: " + event.code + "::" + event.message);
    }
}

//--------------------------------------
//  HELPER METHODS
//--------------------------------------

function addStream(stream) {
    // Check if this is the stream that I am publishing, and if so do not publish.
    if (stream.connection.connectionId == session.connection.connectionId) {
        return;
    }
    var subscriberDiv = document.createElement('div'); // Create a div for the subscriber to replace
    subscriberDiv.setAttribute('id', stream.streamId); // Give the replacement div the id of the stream as its id.
    document.getElementById("subscribers").appendChild(subscriberDiv);
    var subscriberProps = {
        width: VIDEO_WIDTH, 
        height: VIDEO_HEIGHT
    };
    subscribers[stream.streamId] = session.subscribe(stream, subscriberDiv.id, subscriberProps);
}