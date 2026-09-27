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
        d.id_disciplinas AS id,
        d.Nome AS nome,
        d.Codigo AS codigo,
        d.Descricao AS descricao,
        d.CargaHoraria AS cargaHoraria,
        d.STATUS AS status,

        GROUP_CONCAT(
            DISTINCT c.Nome
            ORDER BY c.Nome
            SEPARATOR ', '
        ) AS curso,

        GROUP_CONCAT(
            DISTINCT p.Nome
            ORDER BY p.Nome
            SEPARATOR ', '
        ) AS professor

    FROM disciplinas d

    LEFT JOIN cursos_disciplinas cd
        ON d.id_disciplinas = cd.id_disciplinas

    LEFT JOIN cursos c
        ON cd.id_cursos = c.id_cursos
        AND c.STATUS = 'A'

    LEFT JOIN professores_disciplinas pd
        ON d.id_disciplinas = pd.id_disciplinas

    LEFT JOIN professores p
        ON pd.id_professores = p.id_professores
        AND p.STATUS = 'A'

    WHERE d.STATUS = 'A'

    GROUP BY
        d.id_disciplinas,
        d.Nome,
        d.Codigo,
        d.Descricao,
        d.CargaHoraria,
        d.STATUS

    ORDER BY d.Nome
";


$resultado = $conexao->query($sql);


if (!$resultado) {

    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Erro ao consultar disciplinas",
        "erro" => $conexao->error
    ]);

    exit;
}


$disciplinas = [];


while ($linha = $resultado->fetch_assoc()) {

    $linha["codigo"] =
        $linha["codigo"] ?? "";

    $linha["descricao"] =
        $linha["descricao"] ?? "";

    $linha["curso"] =
        $linha["curso"] ?? "";

    $linha["professor"] =
        $linha["professor"] ?? "";

    $disciplinas[] = $linha;
}


echo json_encode([
    "sucesso" => true,
    "quantidade" => count($disciplinas),
    "dados" => $disciplinas
],
JSON_UNESCAPED_UNICODE |
JSON_PRETTY_PRINT);


$conexao->close();

?>