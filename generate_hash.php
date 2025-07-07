<?php
// Run this script with: php generate_hash.php
$passwords = [
    'admin123',
    'student123',
    'teacher123'
];
foreach ($passwords as $pw) {
    echo $pw . ': ' . password_hash($pw, PASSWORD_DEFAULT) . "\n";
} 