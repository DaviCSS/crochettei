<?php
require 'public/index.php';
$db = \Config\Database::connect();
$res = $db->query('SELECT COUNT(*) as c FROM produtos');
$row = $res->getRow();
echo 'Products: ' . $row->c;
