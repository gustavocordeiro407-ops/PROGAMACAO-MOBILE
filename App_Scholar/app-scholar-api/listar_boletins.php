<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") exit;
require_once "conexao.php";

$sql = "
SELECT
  b.id_boletins AS id,
  a.Nome AS aluno,
  a.RA_aluno AS ra,
  t.Sala AS turma,
  c.Nome AS curso,
  b.Periodo AS periodo,
  CASE
    WHEN b.DataEmissao IS NULL THEN ''
    ELSE DATE_FORMAT(b.DataEmissao, '%d/%m/%Y')
  END AS dataEmissao,
  b.MediaFinal AS mediaFinal,
  b.SituacaoFinal AS situacaoFinal,
  b.Frequencia AS frequencia,
  b.Observacoes AS observacoes,
  b.STATUS AS status
FROM boletins b
INNER JOIN matriculas m
  ON b.id_matriculas = m.id_matriculas
INNER JOIN alunos a
  ON m.id_alunos = a.id_alunos
INNER JOIN turmas t
  ON m.id_turmas = t.id_turmas
INNER JOIN cursos c
  ON t.id_cursos = c.id_cursos
WHERE b.STATUS = 'A'
  AND a.STATUS = 'A'
ORDER BY b.DataEmissao DESC, b.id_boletins DESC
";

$resultado = $conexao->query($sql);

if (!$resultado) {
  http_response_code(500);
  echo json_encode([
    "sucesso" => false,
    "mensagem" => "Erro ao consultar boletins",
    "erro" => $conexao->error
  ]);
  exit;
}

$dados = [];
while ($linha = $resultado->fetch_assoc()) {
  $linha["periodo"] = $linha["periodo"] ?? "";
  $linha["mediaFinal"] = $linha["mediaFinal"] ?? "";
  $linha["situacaoFinal"] = $linha["situacaoFinal"] ?? "";
  $linha["frequencia"] = $linha["frequencia"] ?? "";
  $linha["observacoes"] = $linha["observacoes"] ?? "";
  $dados[] = $linha;
}

echo json_encode([
  "sucesso" => true,
  "quantidade" => count($dados),
  "dados" => $dados
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

$conexao->close();
?>