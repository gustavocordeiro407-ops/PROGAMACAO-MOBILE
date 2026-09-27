<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") exit;
require_once "conexao.php";

$sql = "
SELECT
  av.id_avaliacoes AS id,
  a.Nome AS aluno,
  a.RA_aluno AS ra,
  d.Nome AS disciplina,
  av.Tipo AS tipo,
  av.Titulo AS titulo,
  DATE_FORMAT(av.DataDaAvaliacao, '%d/%m/%Y') AS dataAvaliacao,
  av.ValorMaximo AS valorMaximo,
  (
    SELECT n.NotaObtida
    FROM notas n
    WHERE n.id_avaliacoes = av.id_avaliacoes
      AND n.id_alunos = av.id_alunos
    ORDER BY n.id_notas DESC
    LIMIT 1
  ) AS notaObtida,
  av.Observacoes AS observacoes,
  av.STATUS AS status
FROM avaliacoes av
INNER JOIN alunos a ON av.id_alunos = a.id_alunos
INNER JOIN disciplinas d ON av.id_disciplinas = d.id_disciplinas
WHERE av.STATUS = 'A'
  AND a.STATUS = 'A'
  AND d.STATUS = 'A'
ORDER BY av.DataDaAvaliacao DESC, av.id_avaliacoes DESC
";

$resultado = $conexao->query($sql);

if (!$resultado) {
  http_response_code(500);
  echo json_encode([
    "sucesso" => false,
    "mensagem" => "Erro ao consultar avaliações",
    "erro" => $conexao->error
  ]);
  exit;
}

$avaliacoes = [];
while ($linha = $resultado->fetch_assoc()) {
  $linha["tipo"] = $linha["tipo"] ?? "";
  $linha["titulo"] = $linha["titulo"] ?? "";
  $linha["valorMaximo"] = $linha["valorMaximo"] ?? "";
  $linha["notaObtida"] = $linha["notaObtida"] ?? "";
  $linha["observacoes"] = $linha["observacoes"] ?? "";
  $avaliacoes[] = $linha;
}

echo json_encode([
  "sucesso" => true,
  "quantidade" => count($avaliacoes),
  "dados" => $avaliacoes
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

$conexao->close();
?>