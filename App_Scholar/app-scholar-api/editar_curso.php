<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

require_once "conexao.php";


if ($_SERVER["REQUEST_METHOD"] !== "PUT") {

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


$id =
    intval($dados["id"] ?? 0);

$nome =
    trim($dados["nome"] ?? "");

$area =
    trim($dados["area"] ?? "");

$descricao =
    trim($dados["descricao"] ?? "");

$duracao =
    intval($dados["duracao"] ?? 0);

$cargaHoraria =
    intval($dados["cargaHoraria"] ?? 0);

$modalidade =
    trim($dados["modalidade"] ?? "");


if (
    $id <= 0 ||
    $nome === "" ||
    $area === "" ||
    $descricao === "" ||
    $duracao <= 0 ||
    $cargaHoraria <= 0 ||
    $modalidade === ""
) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Dados incompletos ou inválidos"
    ]);

    exit;
}


// VERIFICA SE O CURSO ESTÁ ATIVO

$stmt = $conexao->prepare("
    SELECT id_cursos
    FROM cursos
    WHERE id_cursos = ?
      AND STATUS = 'A'
    LIMIT 1
");


$stmt->bind_param(
    "i",
    $id
);


$stmt->execute();


$resultado =
    $stmt->get_result();


if ($resultado->num_rows === 0) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Curso não encontrado ou está inativo"
    ]);

    exit;
}


$stmt->close();


// EVITA NOME DUPLICADO

$stmt = $conexao->prepare("
    SELECT id_cursos
    FROM cursos
    WHERE Nome = ?
      AND id_cursos <> ?
    LIMIT 1
");


$stmt->bind_param(
    "si",
    $nome,
    $id
);


$stmt->execute();


$resultado =
    $stmt->get_result();


if ($resultado->num_rows > 0) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Já existe outro curso com esse nome"
    ]);

    exit;
}


$stmt->close();


// ATUALIZA

$stmt = $conexao->prepare("
    UPDATE cursos
    SET
        Nome = ?,
        Area = ?,
        CargaHoraria = ?,
        Duracao = ?,
        Descricao = ?,
        Modalidade = ?
    WHERE id_cursos = ?
      AND STATUS = 'A'
");


$stmt->bind_param(
    "ssiissi",
    $nome,
    $area,
    $cargaHoraria,
    $duracao,
    $descricao,
    $modalidade,
    $id
);


$stmt->execute();


echo json_encode([
    "sucesso" => true,
    "mensagem" =>
        "Curso atualizado com sucesso"
]);


$stmt->close();
$conexao->close();

?>