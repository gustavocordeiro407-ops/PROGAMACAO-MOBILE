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
            "Preencha todos os campos corretamente"
    ]);

    exit;
}


// VERIFICA CÓDIGO DUPLICADO

$stmt = $conexao->prepare("
    SELECT id_disciplinas
    FROM disciplinas
    WHERE Codigo = ?
    LIMIT 1
");


$stmt->bind_param(
    "s",
    $codigo
);


$stmt->execute();


$resultado =
    $stmt->get_result();


if ($resultado->num_rows > 0) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Já existe uma disciplina com esse código"
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

    // DISCIPLINA

    $stmt = $conexao->prepare("
        INSERT INTO disciplinas
        (
            CargaHoraria,
            Nome,
            Codigo,
            Descricao,
            STATUS
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            'A'
        )
    ");


    $stmt->bind_param(
        "isss",
        $cargaHoraria,
        $nome,
        $codigo,
        $descricao
    );


    $stmt->execute();


    $idDisciplina =
        $conexao->insert_id;


    $stmt->close();


    // RELAÇÃO COM CURSO

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
        $idDisciplina
    );


    $stmt->execute();

    $stmt->close();


    // RELAÇÃO COM PROFESSOR

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
            "Disciplina cadastrada com sucesso",
        "id" =>
            $idDisciplina
    ]);


} catch (Throwable $erro) {

    $conexao->rollback();

    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Erro ao cadastrar disciplina",
        "erro" =>
            $erro->getMessage()
    ]);

}


$conexao->close();

?>