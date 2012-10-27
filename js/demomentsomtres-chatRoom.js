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
}