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


$id =
    intval($dados["id"] ?? 0);

$nome =
    trim($dados["nome"] ?? "");

$dataNascimento =
    trim($dados["dataNascimento"] ?? "");

$cpf =
    trim($dados["cpf"] ?? "");

$email =
    trim($dados["email"] ?? "");

$telefone =
    trim($dados["telefone"] ?? "");

$disciplina =
    trim($dados["disciplina"] ?? "");

$formacao =
    trim($dados["formacao"] ?? "");

$dataAdmissao =
    trim($dados["dataAdmissao"] ?? "");


if (
    $id <= 0 ||
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
        "mensagem" => "Dados incompletos"
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


// FORMAÇÃO

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

$resultado =
    $stmt->get_result();


if ($resultado->num_rows === 0) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Formação não encontrada no banco"
    ]);

    exit;
}


$linha =
    $resultado->fetch_assoc();

$idFormacao =
    intval($linha["id_formacoes"]);

$stmt->close();


// DISCIPLINA PRINCIPAL

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

$resultado =
    $stmt->get_result();


if ($resultado->num_rows === 0) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Disciplina não encontrada no banco"
    ]);

    exit;
}


$linha =
    $resultado->fetch_assoc();

$idDisciplina =
    intval(
        $linha["id_disciplinas"]
    );

$stmt->close();


$conexao->begin_transaction();


try {

    // PROFESSOR

    $stmt = $conexao->prepare("
        SELECT id_contatos
        FROM professores
        WHERE id_professores = ?
          AND STATUS = 'A'
        LIMIT 1
        FOR UPDATE
    ");

    $stmt->bind_param(
        "i",
        $id
    );

    $stmt->execute();

    $resultadoProfessor =
        $stmt->get_result();


    if (
        $resultadoProfessor->num_rows === 0
    ) {

        throw new Exception(
            "Professor não encontrado ou inativo"
        );

    }


    $linhaProfessor =
        $resultadoProfessor->fetch_assoc();

    $idContato =
        $linhaProfessor["id_contatos"];

    $stmt->close();


    $stmt = $conexao->prepare("
        UPDATE professores
        SET
            Nome = ?,
            CPF = ?,
            id_formacoes = ?,
            DataDeNascimento = ?,
            DataAdmissao = ?
        WHERE id_professores = ?
          AND STATUS = 'A'
    ");

    $stmt->bind_param(
        "ssissi",
        $nome,
        $cpf,
        $idFormacao,
        $dataNascimentoBanco,
        $dataAdmissaoBanco,
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
            UPDATE professores
            SET id_contatos = ?
            WHERE id_professores = ?
        ");

        $stmt->bind_param(
            "ii",
            $novoContato,
            $id
        );

        $stmt->execute();

        $stmt->close();

    }


    // RELAÇÃO COM DISCIPLINA
    // NÃO APAGA NENHUMA RELAÇÃO.
    // ATUALIZA A PRINCIPAL.

    $stmt = $conexao->prepare("
        SELECT id_professores_disciplinas
        FROM professores_disciplinas
        WHERE id_professores = ?
        ORDER BY id_professores_disciplinas
        LIMIT 1
    ");

    $stmt->bind_param(
        "i",
        $id
    );

    $stmt->execute();

    $resultadoRelacao =
        $stmt->get_result();


    if ($resultadoRelacao->num_rows > 0) {

        $linhaRelacao =
            $resultadoRelacao->fetch_assoc();

        $idRelacao =
            intval(
                $linhaRelacao[
                    "id_professores_disciplinas"
                ]
            );

        $stmt->close();


        $stmt = $conexao->prepare("
            UPDATE professores_disciplinas
            SET id_disciplinas = ?
            WHERE id_professores_disciplinas = ?
        ");

        $stmt->bind_param(
            "ii",
            $idDisciplina,
            $idRelacao
        );

        $stmt->execute();

        $stmt->close();

    } else {

        $stmt->close();


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
            $id,
            $idDisciplina
        );

        $stmt->execute();

        $stmt->close();

    }


    $conexao->commit();


    echo json_encode([
        "sucesso" => true,
        "mensagem" =>
            "Professor atualizado com sucesso"
    ]);


} catch (Throwable $erro) {

    $conexao->rollback();

    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Erro ao atualizar professor",
        "erro" =>
            $erro->getMessage()
    ]);

}


$conexao->close();

?>