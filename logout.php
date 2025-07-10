<?php
// logout.php
// This script handles the user logout process.

// 1. Start the session
// It's necessary to start the session to be able to access and then destroy it.
session_start();

// 2. Unset all of the session variables
// This clears all data stored in the session.
$_SESSION = array();

// 3. Destroy the session
// This completely removes the session from the server.
session_destroy();

// 4. Redirect to the login page
// After logging out, the user is sent back to the main login page.
// Change 'index.php' to your actual login page file if it's different.
header("Location: index.php");
exit; // Ensures no further code is executed after the redirect.
?>