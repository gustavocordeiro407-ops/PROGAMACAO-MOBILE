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
        "mensagem" =>
            "Método não permitido"
    ]);

    exit;
}


$dados = json_decode(
    file_get_contents("php://input"),
    true
);


$id =
    intval($dados["id"] ?? 0);

$sala =
    trim($dados["sala"] ?? "");

$turno =
    trim($dados["turno"] ?? "");

$anoLetivo =
    trim($dados["anoLetivo"] ?? "");

$curso =
    trim($dados["curso"] ?? "");


if (
    $id <= 0 ||
    $sala === "" ||
    $turno === "" ||
    $anoLetivo === "" ||
    $curso === ""
) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Dados incompletos"
    ]);

    exit;
}


if (!preg_match('/^\d{4}$/', $anoLetivo)) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "O ano letivo deve possuir 4 números"
    ]);

    exit;
}


// PROCURA CURSO

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


// VERIFICA DUPLICAÇÃO

$stmt = $conexao->prepare("
    SELECT id_turmas
    FROM turmas
    WHERE Sala = ?
      AND AnoLetivo = ?
      AND id_turmas <> ?
      AND STATUS = 'A'
    LIMIT 1
");


$stmt->bind_param(
    "ssi",
    $sala,
    $anoLetivo,
    $id
);


$stmt->execute();


$resultadoDuplicado =
    $stmt->get_result();


if ($resultadoDuplicado->num_rows > 0) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Já existe outra turma com essa sala nesse ano letivo"
    ]);

    exit;
}


$stmt->close();


// ATUALIZA

$stmt = $conexao->prepare("
    UPDATE turmas
    SET
        Sala = ?,
        Turno = ?,
        AnoLetivo = ?,
        id_cursos = ?
    WHERE id_turmas = ?
      AND STATUS = 'A'
");


$stmt->bind_param(
    "sssii",
    $sala,
    $turno,
    $anoLetivo,
    $idCurso,
    $id
);


$stmt->execute();


if ($stmt->affected_rows >= 0) {

    echo json_encode([
        "sucesso" => true,
        "mensagem" =>
            "Turma atualizada com sucesso"
    ]);

} else {

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Não foi possível atualizar a turma"
    ]);

}


$stmt->close();
$conexao->close();

?>