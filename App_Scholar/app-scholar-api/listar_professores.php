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
        p.id_professores AS id,
        p.Nome AS nome,
        p.CPF AS cpf,

        CASE
            WHEN p.DataDeNascimento IS NULL
            THEN ''
            ELSE DATE_FORMAT(
                p.DataDeNascimento,
                '%d/%m/%Y'
            )
        END AS dataNascimento,

        CASE
            WHEN p.DataAdmissao IS NULL
            THEN ''
            ELSE DATE_FORMAT(
                p.DataAdmissao,
                '%d/%m/%Y'
            )
        END AS dataAdmissao,

        c.Email AS email,
        c.Telefone AS telefone,

        f.Formacao AS formacao,

        GROUP_CONCAT(
            DISTINCT d.Nome
            ORDER BY d.Nome
            SEPARATOR ', '
        ) AS disciplina,

        p.STATUS AS status

    FROM professores p

    LEFT JOIN contatos c
        ON p.id_contatos = c.id_contatos

    LEFT JOIN formacoes f
        ON p.id_formacoes = f.id_formacoes

    LEFT JOIN professores_disciplinas pd
        ON p.id_professores = pd.id_professores

    LEFT JOIN disciplinas d
        ON pd.id_disciplinas = d.id_disciplinas

    WHERE p.STATUS = 'A'

    GROUP BY
        p.id_professores,
        p.Nome,
        p.CPF,
        p.DataDeNascimento,
        p.DataAdmissao,
        c.Email,
        c.Telefone,
        f.Formacao,
        p.STATUS

    ORDER BY p.Nome
";


$resultado = $conexao->query($sql);


if (!$resultado) {

    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Erro ao consultar professores",
        "erro" =>
            $conexao->error
    ]);

    exit;

}


$professores = [];


while (
    $linha =
        $resultado->fetch_assoc()
) {

    if ($linha["disciplina"] === null) {

        $linha["disciplina"] = "";

    }

    if ($linha["formacao"] === null) {

        $linha["formacao"] = "";

    }

    if ($linha["email"] === null) {

        $linha["email"] = "";

    }

    if ($linha["telefone"] === null) {

        $linha["telefone"] = "";

    }


    $professores[] = $linha;

}


echo json_encode([
    "sucesso" => true,
    "quantidade" =>
        count($professores),
    "dados" =>
        $professores
],
JSON_UNESCAPED_UNICODE |
JSON_PRETTY_PRINT);


$conexao->close();

?>