<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "conexao.php";

echo json_encode([
    "sucesso" => true,
    "mensagem" => "APP Scholar conectado ao banco escola"
]);

?>