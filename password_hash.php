<?php
$new_password = 'penko123';
$new_hash = password_hash($new_password, PASSWORD_DEFAULT);
echo $new_hash;
?>