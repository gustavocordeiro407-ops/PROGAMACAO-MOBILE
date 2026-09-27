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

$aluno =
    trim($dados["aluno"] ?? "");

$turma =
    trim($dados["turma"] ?? "");

$curso =
    trim($dados["curso"] ?? "");

$dataMatricula =
    trim($dados["dataMatricula"] ?? "");

$anoLetivo =
    trim($dados["anoLetivo"] ?? "");

$situacao =
    trim($dados["situacao"] ?? "");

$observacoes =
    trim($dados["observacoes"] ?? "");


if (
    $id <= 0 ||
    $aluno === "" ||
    $turma === "" ||
    $curso === "" ||
    $dataMatricula === "" ||
    $anoLetivo === "" ||
    $situacao === ""
) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Dados incompletos"
    ]);

    exit;
}


// DATA

$partes =
    explode("/", $dataMatricula);


if (count($partes) !== 3) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Data inválida"
    ]);

    exit;
}


$dia =
    intval($partes[0]);

$mes =
    intval($partes[1]);

$ano =
    intval($partes[2]);


if (!checkdate($mes, $dia, $ano)) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Data inválida"
    ]);

    exit;
}


$dataBanco =
    sprintf(
        "%04d-%02d-%02d",
        $ano,
        $mes,
        $dia
    );


// ALUNO

$stmt = $conexao->prepare("
    SELECT id_alunos

    FROM alunos

    WHERE Nome = ?
      AND STATUS = 'A'
");


$stmt->bind_param(
    "s",
    $aluno
);


$stmt->execute();


$resultadoAluno =
    $stmt->get_result();


if ($resultadoAluno->num_rows !== 1) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Aluno não encontrado ou nome duplicado"
    ]);

    exit;
}


$linhaAluno =
    $resultadoAluno->fetch_assoc();


$idAluno =
    intval(
        $linhaAluno["id_alunos"]
    );


$stmt->close();


// TURMA

$stmt = $conexao->prepare("
    SELECT
        t.id_turmas

    FROM turmas t

    INNER JOIN cursos c
        ON t.id_cursos = c.id_cursos

    WHERE t.Sala = ?
      AND c.Nome = ?
      AND t.AnoLetivo = ?
      AND t.STATUS = 'A'
      AND c.STATUS = 'A'

    LIMIT 1
");


$stmt->bind_param(
    "sss",
    $turma,
    $curso,
    $anoLetivo
);


$stmt->execute();


$resultadoTurma =
    $stmt->get_result();


if ($resultadoTurma->num_rows === 0) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Turma, curso ou ano letivo inválidos"
    ]);

    exit;
}


$linhaTurma =
    $resultadoTurma->fetch_assoc();


$idTurma =
    intval(
        $linhaTurma["id_turmas"]
    );


$stmt->close();


// EVITA MATRÍCULA DUPLICADA

$stmt = $conexao->prepare("
    SELECT id_matriculas

    FROM matriculas

    WHERE id_alunos = ?
      AND id_matriculas <> ?
      AND STATUS = 'A'

    LIMIT 1
");


$stmt->bind_param(
    "ii",
    $idAluno,
    $id
);


$stmt->execute();


$resultadoDuplicado =
    $stmt->get_result();


if ($resultadoDuplicado->num_rows > 0) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Este aluno já possui outra matrícula ativa"
    ]);

    exit;
}


$stmt->close();


// ATUALIZA

$stmt = $conexao->prepare("
    UPDATE matriculas

    SET
        EstadoDaMatricula = ?,
        DataDaMatricula = ?,
        Observacoes = ?,
        id_alunos = ?,
        id_turmas = ?

    WHERE id_matriculas = ?
      AND STATUS = 'A'
");


$stmt->bind_param(
    "sssiii",
    $situacao,
    $dataBanco,
    $observacoes,
    $idAluno,
    $idTurma,
    $id
);


$stmt->execute();


echo json_encode([
    "sucesso" => true,
    "mensagem" =>
        "Matrícula atualizada com sucesso"
]);


$stmt->close();
$conexao->close();

?>