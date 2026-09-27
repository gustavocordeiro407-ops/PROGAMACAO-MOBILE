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


$sala =
    trim($dados["sala"] ?? "");

$turno =
    trim($dados["turno"] ?? "");

$anoLetivo =
    trim($dados["anoLetivo"] ?? "");

$curso =
    trim($dados["curso"] ?? "");


if (
    $sala === "" ||
    $turno === "" ||
    $anoLetivo === "" ||
    $curso === ""
) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Preencha todos os campos"
    ]);

    exit;
}


if (!preg_match('/^\d{4}$/', $anoLetivo)) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "O ano letivo deve possuir 4 números"
    ]);

    exit;
}


// PROCURA O CURSO

$stmt = $conexao->prepare("
    SELECT id_cursos
    FROM cursos
    WHERE Nome = ?
    LIMIT 1
");


$stmt->bind_param(
    "s",
    $curso
);


$stmt->execute();


$resultadoCurso =
    $stmt->get_result();


if ($resultadoCurso->num_rows === 0) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Curso não encontrado no banco"
    ]);

    exit;
}


$linhaCurso =
    $resultadoCurso->fetch_assoc();


$idCurso =
    intval(
        $linhaCurso["id_cursos"]
    );


$stmt->close();


// EVITA DUPLICAR A MESMA SALA
// NO MESMO ANO LETIVO

$stmt = $conexao->prepare("
    SELECT id_turmas
    FROM turmas
    WHERE Sala = ?
      AND AnoLetivo = ?
      AND STATUS = 'A'
    LIMIT 1
");


$stmt->bind_param(
    "ss",
    $sala,
    $anoLetivo
);


$stmt->execute();


$resultadoTurma =
    $stmt->get_result();


if ($resultadoTurma->num_rows > 0) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Já existe uma turma com essa sala nesse ano letivo"
    ]);

    exit;
}


$stmt->close();


// CADASTRA

$stmt = $conexao->prepare("
    INSERT INTO turmas
    (
        Sala,
        Turno,
        AnoLetivo,
        id_cursos,
        STATUS
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        'A'
    )
");


$stmt->bind_param(
    "sssi",
    $sala,
    $turno,
    $anoLetivo,
    $idCurso
);


$stmt->execute();


$idTurma =
    $conexao->insert_id;


echo json_encode([
    "sucesso" => true,
    "mensagem" =>
        "Turma cadastrada com sucesso",
    "id" => $idTurma
]);


$stmt->close();
$conexao->close();

?>