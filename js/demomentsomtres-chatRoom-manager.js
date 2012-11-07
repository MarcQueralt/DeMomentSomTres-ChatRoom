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
    connect();
}
jQuery("#refreshLink").click(function() {
    refresh_list(); 
});
jQuery("#goLink").click(function() {
    waiting_list_go(1);
});
jQuery("#p2pStopLink").click(function() {
    p2pStopPublishing();
});
refresh_list();

//--------------------------------------
//  LINK CLICK HANDLERS
//--------------------------------------

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
            sessionReset();
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

/** 
 * updates list status in list
 */
function refresh_list() {
    var dataToSend={
        type: "GET",
        url: WP_ADMIN_URL,
        timeout:2000,
        dataType: 'html',
        data: 'action=dmst_chatRoom_pretty_list',
        success: function(data){
            jQuery('#waitingList').html(data);
            window.setTimeout(refresh_list,5000);
        },
        error: function(XMLHttpRequest,textStatus,errorThrown) {
            window.setTimeout(refresh_list,5000);
        }
    };
    jQuery.ajax(dataToSend);        
}

function waiting_list_go(pos) {
    var dataToSend={
        type: "GET",
        url: WP_ADMIN_URL,
        dataType: 'json',
        data: 'action=dmst_chatRoom_to_chatRoom&pos='+pos,
        success: function(data){
            if(!(data=="")) {
                var result=data;
                p2pSessionId=result['id'];
                p2pToken=result['token'];
                p2pStartPublishing();
            }
        }
    };
    jQuery.ajax(dataToSend);
}

// P2P Publishing
//function start_p2p_session() {
//    p2pSession=TB.initSession(p2pSessionId);
//    p2pSession.addEventListener('sessionConnected', p2pSessionConnectedHandler);
//    p2pSession.addEventListener('sessionDisconnected', p2pSessionDisconnectedHandler);
//    p2pSession.addEventListener('connectionCreated', p2pConnectionCreatedHandler);
//    p2pSession.addEventListener('connectionDestroyed', p2pConnectionDestroyedHandler);
//    p2pSession.addEventListener('streamCreated', p2pStreamCreatedHandler);
//    p2pSession.addEventListener('streamDestroyed', p2pStreamDestroyedHandler);
//    var dataToSend={
//        type: "GET",
//        url: WP_ADMIN_URL,
//        dataType: 'json',
//        data: 'action=dmst_chatRoom_p2pToken',
//        success: function(data){
//            if(!(data=="")) {
//                p2pToken=data;
//                stopPublishing();
//                p2pSession.connect(apiKey,p2pToken);
//                p2pStartPublishing();
//            }
//        }
//    };
//    jQuery.ajax(dataToSend);
//}

function p2pStartPublishing() {
    p2pSession=TB.initSession(p2pSessionId);
    p2pSession.addEventListener('sessionConnected', p2pSessionConnectedHandler);
    p2pSession.addEventListener('sessionDisconnected', p2pSessionDisconnectedHandler);
    p2pSession.addEventListener('connectionCreated', p2pConnectionCreatedHandler);
    p2pSession.addEventListener('connectionDestroyed', p2pConnectionDestroyedHandler);
    p2pSession.addEventListener('streamCreated', p2pStreamCreatedHandler);
    p2pSession.addEventListener('streamDestroyed', p2pStreamDestroyedHandler);
    p2pSession.connect(apiKey,p2pToken);
    if (!p2pPublisher) {
        var parentDiv = document.getElementById("p2pMe");
        var publisherDiv = document.createElement('div');
        publisherDiv.setAttribute('id', 'opentok_p2p_publisher');
        parentDiv.appendChild(publisherDiv);
        var publisherProps = {
            width: VIDEO_WIDTH, 
            height: VIDEO_HEIGHT
        };
        p2pPublisher = TB.initPublisher(apiKey, publisherDiv.id, publisherProps);  // Pass the replacement div id and properties
    //        p2pSession.publish(p2pPublisher);
    }
}

function p2pStopPublishing() {
    // if (p2pPublisher) {
    //     p2pSession.unpublish(p2pPublisher);
    // }
    p2pSession.disconnect();
    sessionReset();
    p2pPublisher = null;
    startPublishing();
}

// P2P EVENT HANDLERS

function p2pSessionConnectedHandler(event) {
    // Subscribe to all streams currently in the Session
    p2pSession.publish(p2pPublisher);
    for (var i = 0; i < event.streams.length; i++) {
        p2pAddStream(event.streams[i]);
    }
    window.setTimeout(stopPublishing,15000);
}

function p2pStreamCreatedHandler(event) {
    // Subscribe to the newly created streams
    for (var i = 0; i < event.streams.length; i++) {
        p2pAddStream(event.streams[i]);
    }
}

function p2pStreamDestroyedHandler(event) {
// This signals that a stream was destroyed. Any Subscribers will automatically be removed.
// This default behaviour can be prevented using event.preventDefault()
}

function p2pSessionDisconnectedHandler(event) {
    // This signals that the user was disconnected from the Session. Any subscribers and publishers
    // will automatically be removed. This default behaviour can be prevented using event.preventDefault()
    p2pPublisher = null;
}

function p2pConnectionDestroyedHandler(event) {
// This signals that connections were destroyed
}

function p2pConnectionCreatedHandler(event) {
// This signals new connections have been created.
}

//--------------------------------------
//  P2P HELPER METHODS
//--------------------------------------

function p2pAddStream(stream) {
    // Check if this is the stream that I am publishing, and if so do not publish.
    if (stream.connection.connectionId == p2pSession.connection.connectionId) {
        return;
    }
    var subscriberDiv = document.createElement('div'); // Create a div for the subscriber to replace
    subscriberDiv.setAttribute('id', stream.streamId); // Give the replacement div the id of the stream as its id.
    document.getElementById("p2pYou").appendChild(subscriberDiv);
    var subscriberProps = {
        width: VIDEO_WIDTH, 
        height: VIDEO_HEIGHT
    };
    p2pSubscribers[stream.streamId] = p2pSession.subscribe(stream, subscriberDiv.id, subscriberProps);
}

function sessionReset() {
    var dataToSend={
        type: "GET",
        url: WP_ADMIN_URL,
        dataType: 'json',
        data: 'action=dmst_chatRoom_resetSession',
        success: function(data){
        }
    };
    jQuery.ajax(dataToSend);    
}