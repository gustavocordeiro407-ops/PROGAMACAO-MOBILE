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


$id = intval($dados["id"] ?? 0);

$nome = trim($dados["nome"] ?? "");
$dataNascimento = trim($dados["dataNascimento"] ?? "");
$cpf = trim($dados["cpf"] ?? "");
$ra = trim($dados["ra"] ?? "");
$email = trim($dados["email"] ?? "");
$telefone = trim($dados["telefone"] ?? "");
$curso = trim($dados["curso"] ?? "");
$turma = trim($dados["turma"] ?? "");


if (
    $id <= 0 ||
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
        "mensagem" => "Dados incompletos"
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


// VERIFICA SE O RA JÁ PERTENCE A OUTRO ALUNO

$stmt = $conexao->prepare("
    SELECT id_alunos
    FROM alunos
    WHERE RA_aluno = ?
      AND id_alunos <> ?
    LIMIT 1
");

$stmt->bind_param(
    "si",
    $ra,
    $id
);

$stmt->execute();

$resultadoRA =
    $stmt->get_result();


if ($resultadoRA->num_rows > 0) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Já existe outro aluno com esse RA"
    ]);

    exit;
}


$stmt->close();


// PROCURA A TURMA INFORMADA

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


$dadosTurma =
    $resultadoTurma->fetch_assoc();

$idTurma =
    intval($dadosTurma["id_turmas"]);

$stmt->close();


$conexao->begin_transaction();


try {

    // BUSCA O ALUNO E O CONTATO

    $stmt = $conexao->prepare("
        SELECT id_contatos
        FROM alunos
        WHERE id_alunos = ?
          AND STATUS = 'A'
        LIMIT 1
        FOR UPDATE
    ");

    $stmt->bind_param(
        "i",
        $id
    );

    $stmt->execute();

    $resultadoAluno =
        $stmt->get_result();


    if ($resultadoAluno->num_rows === 0) {

        throw new Exception(
            "Aluno não encontrado ou está inativo"
        );

    }


    $dadosAluno =
        $resultadoAluno->fetch_assoc();

    $idContato =
        $dadosAluno["id_contatos"];

    $stmt->close();


    // ATUALIZA DADOS DO ALUNO

    $stmt = $conexao->prepare("
        UPDATE alunos
        SET
            Nome = ?,
            DataDeNascimento = ?,
            CPF = ?,
            RA_aluno = ?
        WHERE id_alunos = ?
          AND STATUS = 'A'
    ");

    $stmt->bind_param(
        "ssssi",
        $nome,
        $dataBanco,
        $cpf,
        $ra,
        $id
    );

    $stmt->execute();

    $stmt->close();


    // ATUALIZA OU CRIA CONTATO

    if ($idContato) {

        $stmt = $conexao->prepare("
            UPDATE contatos
            SET
                Telefone = ?,
                Email = ?
            WHERE id_contatos = ?
        ");

        $stmt->bind_param(
            "ssi",
            $telefone,
            $email,
            $idContato
        );

        $stmt->execute();

        $stmt->close();

    } else {

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

        $novoIdContato =
            $conexao->insert_id;

        $stmt->close();


        $stmt = $conexao->prepare("
            UPDATE alunos
            SET id_contatos = ?
            WHERE id_alunos = ?
        ");

        $stmt->bind_param(
            "ii",
            $novoIdContato,
            $id
        );

        $stmt->execute();

        $stmt->close();

    }


    // PROCURA MATRÍCULA ATIVA

    $stmt = $conexao->prepare("
        SELECT id_matriculas
        FROM matriculas
        WHERE id_alunos = ?
          AND EstadoDaMatricula = 'Ativa'
        ORDER BY id_matriculas DESC
        LIMIT 1
    ");

    $stmt->bind_param(
        "i",
        $id
    );

    $stmt->execute();

    $resultadoMatricula =
        $stmt->get_result();


    if ($resultadoMatricula->num_rows > 0) {

        $dadosMatricula =
            $resultadoMatricula->fetch_assoc();

        $idMatricula =
            intval(
                $dadosMatricula["id_matriculas"]
            );

        $stmt->close();


        $stmt = $conexao->prepare("
            UPDATE matriculas
            SET id_turmas = ?
            WHERE id_matriculas = ?
        ");

        $stmt->bind_param(
            "ii",
            $idTurma,
            $idMatricula
        );

        $stmt->execute();

        $stmt->close();

    } else {

        $stmt->close();


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
            $id,
            $idTurma
        );

        $stmt->execute();

        $stmt->close();

    }


    $conexao->commit();


    echo json_encode([
        "sucesso" => true,
        "mensagem" => "Aluno atualizado com sucesso"
    ]);


} catch (Throwable $erro) {

    $conexao->rollback();

    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Erro ao atualizar aluno",
        "erro" => $erro->getMessage()
    ]);

}


$conexao->close();

?>