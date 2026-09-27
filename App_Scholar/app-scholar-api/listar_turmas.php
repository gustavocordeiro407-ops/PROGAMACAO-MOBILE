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
        t.id_turmas AS id,
        t.Sala AS sala,
        t.Turno AS turno,
        t.AnoLetivo AS anoLetivo,
        c.Nome AS curso,
        t.STATUS AS status

    FROM turmas t

    LEFT JOIN cursos c
        ON t.id_cursos = c.id_cursos

    WHERE t.STATUS = 'A'

    ORDER BY
        t.AnoLetivo DESC,
        t.Sala ASC
";


$resultado = $conexao->query($sql);


if (!$resultado) {

    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Erro ao consultar turmas",
        "erro" => $conexao->error
    ]);

    exit;
}


$turmas = [];


while ($linha = $resultado->fetch_assoc()) {

    if ($linha["curso"] === null) {
        $linha["curso"] = "";
    }

    $turmas[] = $linha;

}


echo json_encode([
    "sucesso" => true,
    "quantidade" => count($turmas),
    "dados" => $turmas
],
JSON_UNESCAPED_UNICODE |
JSON_PRETTY_PRINT);


$conexao->close();

?>