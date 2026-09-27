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
$email = trim($dados["email"] ?? "");
$telefone = trim($dados["telefone"] ?? "");
$disciplina = trim($dados["disciplina"] ?? "");
$formacao = trim($dados["formacao"] ?? "");
$dataAdmissao = trim($dados["dataAdmissao"] ?? "");


if (
    $nome === "" ||
    $dataNascimento === "" ||
    $cpf === "" ||
    $email === "" ||
    $telefone === "" ||
    $disciplina === "" ||
    $formacao === "" ||
    $dataAdmissao === ""
) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Preencha todos os campos"
    ]);

    exit;
}


function converterData($data) {

    $partes = explode("/", $data);

    if (count($partes) !== 3) {
        return false;
    }

    $dia = intval($partes[0]);
    $mes = intval($partes[1]);
    $ano = intval($partes[2]);

    if (!checkdate($mes, $dia, $ano)) {
        return false;
    }

    return sprintf(
        "%04d-%02d-%02d",
        $ano,
        $mes,
        $dia
    );
}


$dataNascimentoBanco =
    converterData($dataNascimento);

$dataAdmissaoBanco =
    converterData($dataAdmissao);


if (
    !$dataNascimentoBanco ||
    !$dataAdmissaoBanco
) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Data inválida"
    ]);

    exit;
}


// PROCURA FORMAÇÃO

$stmt = $conexao->prepare("
    SELECT id_formacoes
    FROM formacoes
    WHERE Formacao = ?
    LIMIT 1
");

$stmt->bind_param(
    "s",
    $formacao
);

$stmt->execute();

$resultadoFormacao =
    $stmt->get_result();


if ($resultadoFormacao->num_rows === 0) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Formação não encontrada no banco"
    ]);

    exit;
}


$linhaFormacao =
    $resultadoFormacao->fetch_assoc();

$idFormacao =
    intval(
        $linhaFormacao["id_formacoes"]
    );

$stmt->close();


// USA A PRIMEIRA DISCIPLINA INFORMADA
// COMO DISCIPLINA PRINCIPAL

$partesDisciplina =
    explode(",", $disciplina);

$disciplinaPrincipal =
    trim($partesDisciplina[0]);


$stmt = $conexao->prepare("
    SELECT id_disciplinas
    FROM disciplinas
    WHERE Nome = ?
    LIMIT 1
");

$stmt->bind_param(
    "s",
    $disciplinaPrincipal
);

$stmt->execute();

$resultadoDisciplina =
    $stmt->get_result();


if ($resultadoDisciplina->num_rows === 0) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Disciplina não encontrada no banco"
    ]);

    exit;
}


$linhaDisciplina =
    $resultadoDisciplina->fetch_assoc();

$idDisciplina =
    intval(
        $linhaDisciplina["id_disciplinas"]
    );

$stmt->close();


$conexao->begin_transaction();


try {

    // CONTATO

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


    // PROFESSOR

    $stmt = $conexao->prepare("
        INSERT INTO professores
        (
            Nome,
            CPF,
            id_contatos,
            id_formacoes,
            DataDeNascimento,
            DataAdmissao,
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
            'A'
        )
    ");

    $stmt->bind_param(
        "ssiiss",
        $nome,
        $cpf,
        $idContato,
        $idFormacao,
        $dataNascimentoBanco,
        $dataAdmissaoBanco
    );

    $stmt->execute();

    $idProfessor =
        $conexao->insert_id;

    $stmt->close();


    // DISCIPLINA PRINCIPAL

    $stmt = $conexao->prepare("
        INSERT INTO professores_disciplinas
        (
            id_professores,
            id_disciplinas
        )
        VALUES (?, ?)
    ");

    $stmt->bind_param(
        "ii",
        $idProfessor,
        $idDisciplina
    );

    $stmt->execute();

    $stmt->close();


    $conexao->commit();


    echo json_encode([
        "sucesso" => true,
        "mensagem" =>
            "Professor cadastrado com sucesso",
        "id" => $idProfessor
    ]);


} catch (Throwable $erro) {

    $conexao->rollback();

    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Erro ao cadastrar professor",
        "erro" =>
            $erro->getMessage()
    ]);

}


$conexao->close();

?>