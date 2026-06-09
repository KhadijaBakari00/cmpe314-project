<?php
$passwords = [
    'Khadija' => '#Saliha123',      
    'Aiya'    => 'Aiya123'
];

foreach ($passwords as $username => $plain_password) {
    $hashed = password_hash($plain_password, PASSWORD_BCRYPT);
    echo "Username: $username\n";
    echo "Hash: $hashed\n\n";
}
?>