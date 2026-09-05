<?php

$password = 'PASSWORD_YANG_KAMU_MASUKKAN';
$hash = 'HASH_YANG_ADA_DI_DATABASE';

var_dump($hash);
var_dump(strlen($hash));
var_dump(password_verify($password, $hash));