<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

require_once "conexao.php";


$sql = "
    SELECT
        a.id_alunos AS id,
        a.Nome AS nome,
        DATE_FORMAT(a.DataDeNascimento, '%d/%m/%Y') AS dataNascimento,
        a.CPF AS cpf,
        a.RA_aluno AS ra,
        ct.Email AS email,
        ct.Telefone AS telefone,
        c.Nome AS curso,
        t.Sala AS turma,
        a.STATUS AS status

    FROM alunos a

    LEFT JOIN contatos ct
        ON a.id_contatos = ct.id_contatos

    LEFT JOIN matriculas m
        ON a.id_alunos = m.id_alunos
        AND m.EstadoDaMatricula = 'Ativa'

    LEFT JOIN turmas t
        ON m.id_turmas = t.id_turmas

    LEFT JOIN cursos c
        ON t.id_cursos = c.id_cursos

    WHERE a.STATUS = 'A'

    ORDER BY a.Nome
";


$resultado = $conexao->query($sql);


if (!$resultado) {

    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Erro ao consultar alunos",
        "erro" => $conexao->error
    ]);

    exit;
}


$alunos = [];


while ($linha = $resultado->fetch_assoc()) {

    $alunos[] = $linha;

}


echo json_encode([
    "sucesso" => true,
    "quantidade" => count($alunos),
    "dados" => $alunos
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);


$conexao->close();

?>