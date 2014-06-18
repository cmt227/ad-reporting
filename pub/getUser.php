<?php
require("../stdlib.php");

$userID = (int)$_GET['u'];
if (empty($userID)) {
    die();
}
$user = User::loadUser($userID);

$output = "UserID: $userID\nName: " . $user->FName . "\nEmail: " . $user->Email . "\nFlags: " . $user->Flags . "\n";
if ($user->Flags & User::FLAG_VIP_ANYWHERE) {
    $output .= "Has Grooveshark Anywhere\n";
}
if ($user->Flags & User::FLAG_VIP) {
    $output .= "Has Grooveshark Plus\n";
}
if ($user->Flags & User::FLAG_VIP_LITE) {
    $output .= "Has Grooveshark Lite\n";
}

if ($user->IsPremium) {
    $output .= "Is a Premium user\n";
} else {
    $output .= "Is NOT a Premium user\n";
}
echo $output;

$cookiename = ini_get('session.name');
if (isset($_COOKIE[$cookiename])) {
    $domain = ini_get('session.cookie_domain');
    setcookie($cookiename, null, -3600, '/', $domain);
}

?>