<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

require_once "conexao.php";


$sql = "
    SELECT
        m.id_matriculas AS id,

        a.Nome AS aluno,
        a.RA_aluno AS ra,

        t.Sala AS turma,
        t.AnoLetivo AS anoLetivo,

        c.Nome AS curso,

        DATE_FORMAT(
            m.DataDaMatricula,
            '%d/%m/%Y'
        ) AS dataMatricula,

        m.EstadoDaMatricula AS situacao,

        m.Observacoes AS observacoes,

        m.STATUS AS status

    FROM matriculas m

    INNER JOIN alunos a
        ON m.id_alunos = a.id_alunos

    INNER JOIN turmas t
        ON m.id_turmas = t.id_turmas

    INNER JOIN cursos c
        ON t.id_cursos = c.id_cursos

    WHERE m.STATUS = 'A'
      AND a.STATUS = 'A'
      AND t.STATUS = 'A'
      AND c.STATUS = 'A'

    ORDER BY
        m.id_matriculas DESC
";


$resultado = $conexao->query($sql);


if (!$resultado) {

    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Erro ao consultar matrículas",
        "erro" =>
            $conexao->error
    ]);

    exit;
}


$matriculas = [];


while ($linha = $resultado->fetch_assoc()) {

    $linha["observacoes"] =
        $linha["observacoes"] ?? "";

    $matriculas[] = $linha;

}


echo json_encode([
    "sucesso" => true,
    "quantidade" =>
        count($matriculas),
    "dados" =>
        $matriculas
],
JSON_UNESCAPED_UNICODE |
JSON_PRETTY_PRINT);


$conexao->close();

?>