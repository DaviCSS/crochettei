<?php

$db = new mysqli('localhost', 'root', '', 'crochettei');

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$db->set_charset("utf8mb4");

$db->query("UPDATE categorias SET nome = 'Acessórios' WHERE nome LIKE '%Acess%'");
$db->query("UPDATE categorias SET nome = 'Decoração' WHERE nome LIKE '%Decora%'");

echo "DB Updated";
$db->close();
