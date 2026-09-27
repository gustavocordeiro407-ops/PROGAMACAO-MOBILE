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


$nome = trim($dados["nome"] ?? "");
$dataNascimento = trim($dados["dataNascimento"] ?? "");
$cpf = trim($dados["cpf"] ?? "");
$ra = trim($dados["ra"] ?? "");
$email = trim($dados["email"] ?? "");
$telefone = trim($dados["telefone"] ?? "");
$curso = trim($dados["curso"] ?? "");
$turma = trim($dados["turma"] ?? "");


if (
    $nome === "" ||
    $dataNascimento === "" ||
    $cpf === "" ||
    $ra === "" ||
    $email === "" ||
    $telefone === "" ||
    $curso === "" ||
    $turma === ""
) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Preencha todos os campos"
    ]);

    exit;
}


// CONVERTE DD/MM/AAAA PARA AAAA-MM-DD
$partesData = explode("/", $dataNascimento);

if (count($partesData) !== 3) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Data de nascimento inválida"
    ]);

    exit;
}


$dataBanco =
    $partesData[2] . "-" .
    $partesData[1] . "-" .
    $partesData[0];


// VERIFICA SE O RA JÁ EXISTE
$stmt = $conexao->prepare("
    SELECT id_alunos
    FROM alunos
    WHERE RA_aluno = ?
");

$stmt->bind_param(
    "s",
    $ra
);

$stmt->execute();

$resultadoRA =
    $stmt->get_result();


if ($resultadoRA->num_rows > 0) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Já existe um aluno com esse RA"
    ]);

    exit;
}

$stmt->close();


// PROCURA A TURMA E O CURSO
$stmt = $conexao->prepare("
    SELECT
        t.id_turmas

    FROM turmas t

    INNER JOIN cursos c
        ON t.id_cursos = c.id_cursos

    WHERE t.Sala = ?
      AND c.Nome = ?

    LIMIT 1
");

$stmt->bind_param(
    "ss",
    $turma,
    $curso
);

$stmt->execute();

$resultadoTurma =
    $stmt->get_result();


if ($resultadoTurma->num_rows === 0) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Curso ou turma não encontrados no banco"
    ]);

    exit;
}


$linhaTurma =
    $resultadoTurma->fetch_assoc();

$idTurma =
    $linhaTurma["id_turmas"];

$stmt->close();


$conexao->begin_transaction();


try {

    // 1 - CONTATO

    $stmt = $conexao->prepare("
        INSERT INTO contatos
        (
            Telefone,
            Email
        )
        VALUES (?, ?)
    ");

    $stmt->bind_param(
        "ss",
        $telefone,
        $email
    );

    $stmt->execute();

    $idContato =
        $conexao->insert_id;

    $stmt->close();


    // 2 - ALUNO

    $numeroCasa = 0;

    $stmt = $conexao->prepare("
        INSERT INTO alunos
        (
            Nome,
            NumeroDaCasa,
            Complemento,
            DataDeNascimento,
            CPF,
            RA_aluno,
            id_ruas,
            id_contatos,
            STATUS
        )
        VALUES
        (
            ?,
            ?,
            NULL,
            ?,
            ?,
            ?,
            NULL,
            ?,
            'A'
        )
    ");

    $stmt->bind_param(
        "sisssi",
        $nome,
        $numeroCasa,
        $dataBanco,
        $cpf,
        $ra,
        $idContato
    );

    $stmt->execute();

    $idAluno =
        $conexao->insert_id;

    $stmt->close();


    // 3 - MATRÍCULA

    $estadoMatricula = "Ativa";
    $dataMatricula = date("Y-m-d");

    $stmt = $conexao->prepare("
        INSERT INTO matriculas
        (
            EstadoDaMatricula,
            DataDaMatricula,
            id_alunos,
            id_turmas
        )
        VALUES (?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssii",
        $estadoMatricula,
        $dataMatricula,
        $idAluno,
        $idTurma
    );

    $stmt->execute();

    $stmt->close();


    $conexao->commit();


    echo json_encode([
        "sucesso" => true,
        "mensagem" => "Aluno cadastrado com sucesso",
        "id" => $idAluno
    ]);


} catch (Throwable $erro) {

    $conexao->rollback();

    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Erro ao cadastrar aluno",
        "erro" => $erro->getMessage()
    ]);

}


$conexao->close();

?>