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
        r.id_responsaveis AS id,
        r.Nome AS nome,
        r.CPF AS cpf,
        r.RG AS rg,
        r.Parentesco AS parentesco,
        r.Endereco AS endereco,
        r.Observacoes AS observacoes,

        c.Telefone AS telefone,
        c.Email AS email,

        r.STATUS AS status

    FROM responsaveis r

    LEFT JOIN contatos c
        ON r.id_contatos = c.id_contatos

    WHERE r.STATUS = 'A'

    ORDER BY r.Nome
";


$resultado = $conexao->query($sql);


if (!$resultado) {

    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Erro ao consultar responsáveis",
        "erro" =>
            $conexao->error
    ]);

    exit;
}


$responsaveis = [];


while ($linha = $resultado->fetch_assoc()) {

    $linha["rg"] =
        $linha["rg"] ?? "";

    $linha["endereco"] =
        $linha["endereco"] ?? "";

    $linha["observacoes"] =
        $linha["observacoes"] ?? "";

    $linha["telefone"] =
        $linha["telefone"] ?? "";

    $linha["email"] =
        $linha["email"] ?? "";

    $responsaveis[] = $linha;

}


echo json_encode([
    "sucesso" => true,
    "quantidade" =>
        count($responsaveis),
    "dados" =>
        $responsaveis
],
JSON_UNESCAPED_UNICODE |
JSON_PRETTY_PRINT);


$conexao->close();

?>