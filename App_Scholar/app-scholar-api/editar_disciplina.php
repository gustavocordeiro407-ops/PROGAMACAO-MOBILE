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

$codigo =
    trim($dados["codigo"] ?? "");

$curso =
    trim($dados["curso"] ?? "");

$descricao =
    trim($dados["descricao"] ?? "");

$cargaHoraria =
    intval($dados["cargaHoraria"] ?? 0);

$professor =
    trim($dados["professor"] ?? "");


if (
    $id <= 0 ||
    $nome === "" ||
    $codigo === "" ||
    $curso === "" ||
    $descricao === "" ||
    $cargaHoraria <= 0 ||
    $professor === ""
) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Dados incompletos ou inválidos"
    ]);

    exit;
}


// VERIFICA CÓDIGO DUPLICADO

$stmt = $conexao->prepare("
    SELECT id_disciplinas
    FROM disciplinas
    WHERE Codigo = ?
      AND id_disciplinas <> ?
    LIMIT 1
");


$stmt->bind_param(
    "si",
    $codigo,
    $id
);


$stmt->execute();


$resultado =
    $stmt->get_result();


if ($resultado->num_rows > 0) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Já existe outra disciplina com esse código"
    ]);

    exit;
}


$stmt->close();


// PROCURA CURSO

$stmt = $conexao->prepare("
    SELECT id_cursos
    FROM cursos
    WHERE Nome = ?
      AND STATUS = 'A'
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
            "Curso não encontrado ou inativo"
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


// PROCURA PROFESSOR

$stmt = $conexao->prepare("
    SELECT id_professores
    FROM professores
    WHERE Nome = ?
      AND STATUS = 'A'
    LIMIT 1
");


$stmt->bind_param(
    "s",
    $professor
);


$stmt->execute();


$resultadoProfessor =
    $stmt->get_result();


if ($resultadoProfessor->num_rows === 0) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Professor não encontrado ou inativo"
    ]);

    exit;
}


$linhaProfessor =
    $resultadoProfessor->fetch_assoc();


$idProfessor =
    intval(
        $linhaProfessor["id_professores"]
    );


$stmt->close();


$conexao->begin_transaction();


try {

    // ATUALIZA DISCIPLINA

    $stmt = $conexao->prepare("
        UPDATE disciplinas
        SET
            Nome = ?,
            Codigo = ?,
            Descricao = ?,
            CargaHoraria = ?
        WHERE id_disciplinas = ?
          AND STATUS = 'A'
    ");


    $stmt->bind_param(
        "sssii",
        $nome,
        $codigo,
        $descricao,
        $cargaHoraria,
        $id
    );


    $stmt->execute();

    $stmt->close();


    // RELAÇÃO COM CURSO

    $stmt = $conexao->prepare("
        SELECT id_cursos_disciplinas
        FROM cursos_disciplinas
        WHERE id_disciplinas = ?
        ORDER BY id_cursos_disciplinas
        LIMIT 1
    ");


    $stmt->bind_param(
        "i",
        $id
    );


    $stmt->execute();


    $resultadoRelacaoCurso =
        $stmt->get_result();


    if (
        $resultadoRelacaoCurso->num_rows > 0
    ) {

        $linha =
            $resultadoRelacaoCurso->fetch_assoc();

        $idRelacao =
            intval(
                $linha["id_cursos_disciplinas"]
            );

        $stmt->close();


        $stmt = $conexao->prepare("
            UPDATE cursos_disciplinas
            SET id_cursos = ?
            WHERE id_cursos_disciplinas = ?
        ");


        $stmt->bind_param(
            "ii",
            $idCurso,
            $idRelacao
        );


        $stmt->execute();

        $stmt->close();

    } else {

        $stmt->close();


        $stmt = $conexao->prepare("
            INSERT INTO cursos_disciplinas
            (
                id_cursos,
                id_disciplinas
            )
            VALUES (?, ?)
        ");


        $stmt->bind_param(
            "ii",
            $idCurso,
            $id
        );


        $stmt->execute();

        $stmt->close();

    }


    // RELAÇÃO COM PROFESSOR

    $stmt = $conexao->prepare("
        SELECT id_professores_disciplinas
        FROM professores_disciplinas
        WHERE id_disciplinas = ?
        ORDER BY id_professores_disciplinas
        LIMIT 1
    ");


    $stmt->bind_param(
        "i",
        $id
    );


    $stmt->execute();


    $resultadoRelacaoProfessor =
        $stmt->get_result();


    if (
        $resultadoRelacaoProfessor->num_rows > 0
    ) {

        $linha =
            $resultadoRelacaoProfessor->fetch_assoc();


        $idRelacao =
            intval(
                $linha[
                    "id_professores_disciplinas"
                ]
            );


        $stmt->close();


        $stmt = $conexao->prepare("
            UPDATE professores_disciplinas
            SET id_professores = ?
            WHERE id_professores_disciplinas = ?
        ");


        $stmt->bind_param(
            "ii",
            $idProfessor,
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
            $idProfessor,
            $id
        );


        $stmt->execute();

        $stmt->close();

    }


    $conexao->commit();


    echo json_encode([
        "sucesso" => true,
        "mensagem" =>
            "Disciplina atualizada com sucesso"
    ]);


} catch (Throwable $erro) {

    $conexao->rollback();

    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Erro ao atualizar disciplina",
        "erro" =>
            $erro->getMessage()
    ]);

}


$conexao->close();

?>