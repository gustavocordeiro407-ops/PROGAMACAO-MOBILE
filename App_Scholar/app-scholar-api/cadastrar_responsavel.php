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


$nome =
    trim($dados["nome"] ?? "");

$cpf =
    trim($dados["cpf"] ?? "");

$rg =
    trim($dados["rg"] ?? "");

$telefone =
    trim($dados["telefone"] ?? "");

$email =
    trim($dados["email"] ?? "");

$parentesco =
    trim($dados["parentesco"] ?? "");

$endereco =
    trim($dados["endereco"] ?? "");

$observacoes =
    trim($dados["observacoes"] ?? "");


if (
    $nome === "" ||
    $cpf === "" ||
    $telefone === "" ||
    $parentesco === ""
) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Preencha todos os campos obrigatórios"
    ]);

    exit;
}


if (!preg_match('/^\d{11}$/', $cpf)) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "O CPF deve possuir 11 números"
    ]);

    exit;
}


if (!preg_match('/^\d{11}$/', $telefone)) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "O telefone deve possuir 11 números"
    ]);

    exit;
}


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


    // RESPONSÁVEL

    $stmt = $conexao->prepare("
        INSERT INTO responsaveis
        (
            Parentesco,
            Nome,
            CPF,
            RG,
            Endereco,
            Observacoes,
            id_contatos,
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
            ?,
            'A'
        )
    ");


    $stmt->bind_param(
        "ssssssi",
        $parentesco,
        $nome,
        $cpf,
        $rg,
        $endereco,
        $observacoes,
        $idContato
    );


    $stmt->execute();


    $idResponsavel =
        $conexao->insert_id;


    $stmt->close();


    $conexao->commit();


    echo json_encode([
        "sucesso" => true,
        "mensagem" =>
            "Responsável cadastrado com sucesso",
        "id" =>
            $idResponsavel
    ]);


} catch (Throwable $erro) {

    $conexao->rollback();

    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Erro ao cadastrar responsável",
        "erro" =>
            $erro->getMessage()
    ]);

}


$conexao->close();

?>