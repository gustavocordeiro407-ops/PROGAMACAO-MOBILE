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


$id = $dados["id"] ?? null;


if (!$id) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "ID do aluno não informado"
    ]);

    exit;
}


$sql = "
    UPDATE alunos
    SET STATUS = 'I'
    WHERE id_alunos = ?
      AND STATUS = 'A'
";


$stmt = $conexao->prepare($sql);

$stmt->bind_param(
    "i",
    $id
);

$stmt->execute();


if ($stmt->affected_rows > 0) {

    echo json_encode([
        "sucesso" => true,
        "mensagem" => "Aluno inativado com sucesso"
    ]);

} else {

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Aluno não encontrado ou já está inativo"
    ]);

}


$stmt->close();
$conexao->close();

?>