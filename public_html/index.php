<?php
// This line gets the "User-Agent" string from the visitor's browser.
$user_agent = $_SERVER['HTTP_USER_AGENT'];

// This if-else statement checks for keywords that indicate a mobile device.
if (strpos($user_agent, 'Mobile') !== false || strpos($user_agent, 'Android') !== false || strpos($user_agent, 'iPhone') !== false) {
    // If it's a mobile device, this will serve the content of mobile.html.
    include('mobile.html');
} else {
    // If it's a desktop device, this will serve the content of desktop.html.
    include('desktop.html');
}
?>