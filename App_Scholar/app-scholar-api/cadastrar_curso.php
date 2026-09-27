<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

require_once "conexao.php";


if ($_SERVER["REQUEST_METHOD"] !== "POST") {

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
            "Preencha todos os campos corretamente"
    ]);

    exit;
}


// EVITA CURSO DUPLICADO

$stmt = $conexao->prepare("
    SELECT id_cursos
    FROM cursos
    WHERE Nome = ?
    LIMIT 1
");


$stmt->bind_param(
    "s",
    $nome
);


$stmt->execute();


$resultado =
    $stmt->get_result();


if ($resultado->num_rows > 0) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Já existe um curso com esse nome"
    ]);

    exit;
}


$stmt->close();


// CADASTRO

$stmt = $conexao->prepare("
    INSERT INTO cursos
    (
        Nome,
        Area,
        CargaHoraria,
        Duracao,
        Descricao,
        Modalidade,
        id_coordenadores,
        STATUS
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        NULL,
        'A'
    )
");


$stmt->bind_param(
    "ssiiss",
    $nome,
    $area,
    $cargaHoraria,
    $duracao,
    $descricao,
    $modalidade
);


$stmt->execute();


$idCurso =
    $conexao->insert_id;


echo json_encode([
    "sucesso" => true,
    "mensagem" =>
        "Curso cadastrado com sucesso",
    "id" => $idCurso
]);


$stmt->close();
$conexao->close();

?>