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
    $id <= 0 ||
    $nome === "" ||
    $cpf === "" ||
    $telefone === "" ||
    $parentesco === ""
) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Dados obrigatórios incompletos"
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

    // BUSCA CONTATO

    $stmt = $conexao->prepare("
        SELECT id_contatos

        FROM responsaveis

        WHERE id_responsaveis = ?
          AND STATUS = 'A'

        LIMIT 1

        FOR UPDATE
    ");


    $stmt->bind_param(
        "i",
        $id
    );


    $stmt->execute();


    $resultado =
        $stmt->get_result();


    if ($resultado->num_rows === 0) {

        throw new Exception(
            "Responsável não encontrado ou inativo"
        );

    }


    $linha =
        $resultado->fetch_assoc();


    $idContato =
        $linha["id_contatos"];


    $stmt->close();


    // RESPONSÁVEL

    $stmt = $conexao->prepare("
        UPDATE responsaveis

        SET
            Nome = ?,
            CPF = ?,
            RG = ?,
            Parentesco = ?,
            Endereco = ?,
            Observacoes = ?

        WHERE id_responsaveis = ?
          AND STATUS = 'A'
    ");


    $stmt->bind_param(
        "ssssssi",
        $nome,
        $cpf,
        $rg,
        $parentesco,
        $endereco,
        $observacoes,
        $id
    );


    $stmt->execute();

    $stmt->close();


    // CONTATO

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


        $novoContato =
            $conexao->insert_id;


        $stmt->close();


        $stmt = $conexao->prepare("
            UPDATE responsaveis

            SET id_contatos = ?

            WHERE id_responsaveis = ?
        ");


        $stmt->bind_param(
            "ii",
            $novoContato,
            $id
        );


        $stmt->execute();

        $stmt->close();

    }


    $conexao->commit();


    echo json_encode([
        "sucesso" => true,
        "mensagem" =>
            "Responsável atualizado com sucesso"
    ]);


} catch (Throwable $erro) {

    $conexao->rollback();

    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Erro ao atualizar responsável",
        "erro" =>
            $erro->getMessage()
    ]);

}


$conexao->close();

?>