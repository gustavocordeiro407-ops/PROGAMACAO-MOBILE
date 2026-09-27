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
        "mensagem" =>
            "Método não permitido"
    ]);

    exit;
}


$dados = json_decode(
    file_get_contents("php://input"),
    true
);


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
            "Preencha todos os campos obrigatórios"
    ]);

    exit;
}


// CONVERTE DATA

$partes =
    explode("/", $dataMatricula);


if (count($partes) !== 3) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Data da matrícula inválida"
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
            "Data da matrícula inválida"
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


// PROCURA ALUNO

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


if ($resultadoAluno->num_rows === 0) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Aluno não encontrado ou inativo"
    ]);

    exit;
}


if ($resultadoAluno->num_rows > 1) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Existe mais de um aluno com esse nome"
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


// PROCURA TURMA + CURSO + ANO

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


// VERIFICA MATRÍCULA ATIVA

$stmt = $conexao->prepare("
    SELECT id_matriculas

    FROM matriculas

    WHERE id_alunos = ?
      AND STATUS = 'A'

    LIMIT 1
");


$stmt->bind_param(
    "i",
    $idAluno
);


$stmt->execute();


$resultadoExistente =
    $stmt->get_result();


if ($resultadoExistente->num_rows > 0) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Este aluno já possui uma matrícula ativa"
    ]);

    exit;
}


$stmt->close();


// CADASTRA

$stmt = $conexao->prepare("
    INSERT INTO matriculas
    (
        EstadoDaMatricula,
        DataDaMatricula,
        Observacoes,
        id_alunos,
        id_turmas,
        STATUS
    )

    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        ?,
        'A'
    )
");


$stmt->bind_param(
    "sssii",
    $situacao,
    $dataBanco,
    $observacoes,
    $idAluno,
    $idTurma
);


$stmt->execute();


$idMatricula =
    $conexao->insert_id;


echo json_encode([
    "sucesso" => true,
    "mensagem" =>
        "Matrícula cadastrada com sucesso",
    "id" =>
        $idMatricula
]);


$stmt->close();
$conexao->close();

?>