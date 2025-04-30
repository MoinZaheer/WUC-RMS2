<?php
$enteredPassword = 'test123'; // the password to check
$storedHash = '$2y$10$HoRC9NQmXHOSroZrLvmczeGvt9NRM8ydYb3kTi9bDO6ItGnp43TyK'; // stored in db

$hash = password_hash($enteredPassword, PASSWORD_DEFAULT);

echo "Plain Password: $enteredPassword<br>";
echo "Hashed Password: <pre>$hash</pre>";


if (password_verify($enteredPassword, $storedHash)) {
    echo "Password matches!";
} else {
    echo "Password does NOT match.";
}
