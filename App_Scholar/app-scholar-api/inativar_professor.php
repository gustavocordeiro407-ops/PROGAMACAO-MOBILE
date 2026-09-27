<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PATCH, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

require_once "conexao.php";


if ($_SERVER["REQUEST_METHOD"] !== "PATCH") {

    http_response_code(405);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Método não permitido"
    ]);

    exit;
}


$dados = json_decode(
    file_get_contents("php://input"),
    true
);


$id = intval(
    $dados["id"] ?? 0
);


if ($id <= 0) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "ID do professor não informado"
    ]);

    exit;
}


$stmt = $conexao->prepare("
    UPDATE professores
    SET STATUS = 'I'
    WHERE id_professores = ?
      AND STATUS = 'A'
");


$stmt->bind_param(
    "i",
    $id
);


$stmt->execute();


if ($stmt->affected_rows > 0) {

    echo json_encode([
        "sucesso" => true,
        "mensagem" => "Professor inativado com sucesso"
    ]);

} else {

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Professor não encontrado ou já está inativo"
    ]);

}


$stmt->close();
$conexao->close();

?>