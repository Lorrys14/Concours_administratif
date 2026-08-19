<?php
$hash = trim(file_get_contents('hash.txt'));
echo "Hash from file: $hash\n";
var_dump(password_verify('admin123', $hash));
