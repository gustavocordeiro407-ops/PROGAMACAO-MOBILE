<?php

$host = "mysql-appscholar.alwaysdata.net";
$usuario = "appscholar";
$senha = "38QRvGBqB@HuuVz";
$banco = "appscholar_escola";


$conexao = new mysqli(
    $host,
    $usuario,
    $senha,
    $banco
);


if ($conexao->connect_error) {

    die(
        json_encode([
            "sucesso" => false,
            "mensagem" => "Erro ao conectar ao banco",
            "erro" => $conexao->connect_error
        ])
    );

}


$conexao->set_charset("utf8mb4");

?>