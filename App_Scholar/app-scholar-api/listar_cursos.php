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
        c.id_cursos AS id,
        c.Nome AS nome,
        c.Area AS area,
        c.Descricao AS descricao,
        c.Duracao AS duracao,
        c.CargaHoraria AS cargaHoraria,
        c.Modalidade AS modalidade,
        c.STATUS AS status,
        co.Nome AS coordenador

    FROM cursos c

    LEFT JOIN coordenadores co
        ON c.id_coordenadores = co.id_coordenadores

    WHERE c.STATUS = 'A'

    ORDER BY c.Nome
";


$resultado = $conexao->query($sql);


if (!$resultado) {

    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Erro ao consultar cursos",
        "erro" => $conexao->error
    ]);

    exit;
}


$cursos = [];


while ($linha = $resultado->fetch_assoc()) {

    $linha["area"] =
        $linha["area"] ?? "";

    $linha["modalidade"] =
        $linha["modalidade"] ?? "";

    $linha["coordenador"] =
        $linha["coordenador"] ?? "";

    $cursos[] = $linha;
}


echo json_encode([
    "sucesso" => true,
    "quantidade" => count($cursos),
    "dados" => $cursos
],
JSON_UNESCAPED_UNICODE |
JSON_PRETTY_PRINT);


$conexao->close();

?>