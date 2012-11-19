// Script adapted by DeMomentSomTres
// for WordPress plugin demomentsomtres-chatRoom.
// more info http://demomentsomtres.com
var containerDiv = document.getElementById("subscribers");
containerDiv.style.height=VIDEO_HEIGHT+"px";
containerDiv.style.width=VIDEO_WIDTH+"px";
containerDiv.style.display="block";
TB.addEventListener("exception", exceptionHandler);		
if (TB.checkSystemRequirements() != TB.HAS_REQUIREMENTS) {
    alert(ERROR_REQUIREMENTS);
} else {
    session = TB.initSession(sessionId);	// Initialize session

    // Add event listeners to the session
    session.addEventListener('sessionConnected', sessionConnectedHandler);
    session.addEventListener('sessionDisconnected', sessionDisconnectedHandler);
    session.addEventListener('connectionCreated', connectionCreatedHandler);
    session.addEventListener('connectionDestroyed', connectionDestroyedHandler);
    session.addEventListener('streamCreated', streamCreatedHandler);
    session.addEventListener('streamDestroyed', streamDestroyedHandler);
    connect(); 
}
jQuery("#turnLink").click(function(){
    ask_your_turn();
    refresh_list();
});
jQuery("#refreshLink").click(function() {
    refresh_list(); 
});
jQuery("#p2p").hide();
check_your_turn();
refresh_list();

//--------------------------------------
//  LINK CLICK HANDLERS
//--------------------------------------

/* If testing the app from the desktop, be sure to check the Flash Player Global Security setting
 * to allow the page from communicating with SWF content loaded from the web. For more information,
 * see http://www.tokbox.com/opentok/build/tutorials/helloworld.html#localTest
 */
function connect() {
    session.connect(apiKey, token);
}

function ask_your_turn() {
    var dataToSend={
        type: "GET",
        url: WP_ADMIN_URL,
        dataType: 'html',
        data: 'action=dmst_chatRoom_add_to_queue&id='+userId,
        success: function(data){
            jQuery('#DeMomentSomTres-chatRoom-messages').html(data);
            refresh_list();
            wait_your_turn();
        }
    };
    jQuery.ajax(dataToSend);
}

function wait_your_turn() {
    var dataToSend={
        type: "GET",
        url: WP_ADMIN_URL,
        dataType: 'html',
        data: 'action=dmst_chatRoom_in_chatRoom',
        success: function(data){
            if(data==userId) {
                prepare_p2p();
            } else {
                window.setTimeout(wait_your_turn,5000);                
            }
        },
        error: function(XMLHttpRequest,textStatus,errorThrown) {
            window.setTimeout(wait_your_turn,5000);
        }
    };
    jQuery.ajax(dataToSend);        
}

function check_your_turn() {
    var dataToSend={
        type: "GET",
        url: WP_ADMIN_URL,
        dataType: 'html',
        data: 'action=dmst_chatRoom_in_chatRoom',
        success: function(data){
            if(data==userId) {
                prepare_p2p();
            }
        }
    };
    jQuery.ajax(dataToSend);        
}

function prepare_p2p() {
    var dataToSend={
        type: "GET",
        url: WP_ADMIN_URL,
        dataType: 'json',
        data: 'action=dmst_chatRoom_p2pSession&userId='+userId,
        success: function(data){
            if(data!="") {
                var result=data;
                p2pSessionId=result['id'];
                p2pToken=result['token'];
                p2pStartPublishing();
            }
        }
    }; 
    jQuery.ajax(dataToSend);        
}
//
//function get_p2p_session() {
//    var dataToSend={
//        type: "GET",
//        url: WP_ADMIN_URL,
//        dataType: 'json',
//        data: 'action=dmst_chatRoom_p2pSession&userId='+userId,
//        success: function(data){
//            if(data!="") {
//                p2pSessionId=data;
//                start_p2p_session();
//            }
//        }
//    }; 
//    jQuery.ajax(dataToSend);        
//}

//--------------------------------------
//  OPENTOK EVENT HANDLERS
//--------------------------------------

function sessionConnectedHandler(event) {
    // Subscribe to all streams currently in the Session
    for (var i = 0; i < event.streams.length; i++) {
        addStream(event.streams[i]);
    }
    if(0==event.streams.length) {
        dmst_check_status();
    } else {
        jQuery("#subscribers").removeClass("dmst_chatRoom_standby");
        jQuery("#subscribers").removeClass("dmst_chatRoom_closed");
        jQuery("#subscribers").addClass("dmst_chatRoom_open");
    }
}

function streamCreatedHandler(event) {
    // Subscribe to the newly created streams
    for (var i = 0; i < event.streams.length; i++) {
        addStream(event.streams[i]);
    }
    jQuery("#subscribers").removeClass("dmst_chatRoom_standby");
    jQuery("#subscribers").removeClass("dmst_chatRoom_closed");
    jQuery("#subscribers").addClass("dmst_chatRoom_open");
}

function streamDestroyedHandler(event) {
    // This signals that a stream was destroyed. Any Subscribers will automatically be removed.
    // This default behaviour can be prevented using event.preventDefault()
    jQuery("#subscribers").addClass("dmst_chatRoom_standby");
    jQuery("#subscribers").removeClass("dmst_chatRoom_closed");
    jQuery("#subscribers").removeClass("dmst_chatRoom_open");
}

function sessionDisconnectedHandler(event) {
    // This signals that the user was disconnected from the Session. Any subscribers and publishers
    // will automatically be removed. This default behaviour can be prevented using event.preventDefault()
    publisher = null;
    jQuery("#subscribers").removeClass("dmst_chatRoom_standby");
    jQuery("#subscribers").addClass("dmst_chatRoom_closed");
    jQuery("#subscribers").removeClass("dmst_chatRoom_open");
}

function connectionDestroyedHandler(event) {
    // This signals that connections were destroyed
    dmst_check_status();
}

function connectionCreatedHandler(event) {
    // This signals new connections have been created.
    dmst_check_status();
}

/* If you un-comment the call to TB.addEventListener("exception", exceptionHandler) above, OpenTok calls the
 * exceptionHandler() method when exception events occur. You can modify this method to further process exception events.
 * If you un-comment the call to TB.setLogLevel(), above, OpenTok automatically displays exception event messages.
 */
function exceptionHandler(event) {
    if (event.code == 1013) {
        jQuery("#DeMomentSomTres-chatRoom-messages").html(P2P_1013_MESSAGE);
    } else {
    //        alert("Exception: " + event.code + "::" + event.message);
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
 * Checks status via ajax and shows or hides attention area anc changes screen styles
 */
function dmst_check_status() {
    var dataToSend={
        type: "GET",
        url: WP_ADMIN_URL,
        dataType: 'html',
        data: 'action=dmst_chatRoom_status',
        success: function(data){
            if(data=='1') {
                jQuery("#subscribers").addClass("dmst_chatRoom_standby");
                jQuery("#subscribers").removeClass("dmst_chatRoom_closed");
                jQuery("#subscribers").removeClass("dmst_chatRoom_open");
                jQuery('#DeMomentSomTres-chatRoom-listAccess').show();
            } else {
                jQuery("#subscribers").removeClass("dmst_chatRoom_standby");
                jQuery("#subscribers").addClass("dmst_chatRoom_closed");
                jQuery("#subscribers").removeClass("dmst_chatRoom_open");
                jQuery('#DeMomentSomTres-chatRoom-listAccess').hide();
            }
        }
    };
    jQuery.ajax(dataToSend);    
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
        data: 'action=dmst_chatRoom_pretty_list&id='+userId,
        success: function(data){
            jQuery('#DeMomentSomTres-chatRoom-waitingList').html(data);
            window.setTimeout(refresh_list,10000);
        },
        error: function(XMLHttpRequest,textStatus,errorThrown) {
            window.setTimeout(refresh_list,10000);
        }
    };
    jQuery.ajax(dataToSend);        
}

// P2P Publishing
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
            width: VIDEO_WIDTH/5, 
            height: VIDEO_HEIGHT/5
        };
        p2pPublisher = TB.initPublisher(apiKey, publisherDiv.id, publisherProps);  // Pass the replacement div id and properties
        jQuery("#p2p").show();
    //        p2pSession.publish(p2pPublisher);
    }
}

function p2pStopPublishing() {
    if (p2pPublisher) {
        p2pSession.unpublish(p2pPublisher);
    }
    p2pPublisher = null;
}

// P2P EVENT HANDLERS

function p2pSessionConnectedHandler(event) {
    p2pSession.publish(p2pPublisher);
    // Subscribe to all streams currently in the Session
    for (var i = 0; i < event.streams.length; i++) {
        p2pAddStream(event.streams[i]);
    }
    jQuery("#p2p").show();
}

function p2pStreamCreatedHandler(event) {
    // Subscribe to the newly created streams
    for (var i = 0; i < event.streams.length; i++) {
        p2pAddStream(event.streams[i]);
    }
    jQuery("#p2p").show();
}

function p2pStreamDestroyedHandler(event) {
    // This signals that a stream was destroyed. Any Subscribers will automatically be removed.
    // This default behaviour can be prevented using event.preventDefault()
    p2pSession.disconnect();
    jQuery("#p2p").hide();
}

function p2pSessionDisconnectedHandler(event) {
    // This signals that the user was disconnected from the Session. Any subscribers and publishers
    // will automatically be removed. This default behaviour can be prevented using event.preventDefault()
    p2pPublisher = null;
    jQuery("#p2p").hide();
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
