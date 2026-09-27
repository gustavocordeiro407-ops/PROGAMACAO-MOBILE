<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") exit;
require_once "conexao.php";

$sql = "
SELECT
  co.id_coordenadores AS id,
  co.Nome AS nome,
  ct.Email AS email,
  ct.Telefone AS telefone,
  co.Departamento AS departamento,
  CASE
    WHEN co.DataAdmissao IS NULL THEN ''
    ELSE DATE_FORMAT(co.DataAdmissao, '%d/%m/%Y')
  END AS dataAdmissao,
  f.Formacao AS formacao,
  co.Observacoes AS observacoes,
  co.STATUS AS status
FROM coordenadores co
LEFT JOIN contatos ct ON co.id_contatos = ct.id_contatos
LEFT JOIN formacoes f ON co.id_formacoes = f.id_formacoes
WHERE co.STATUS = 'A'
ORDER BY co.Nome
";

$resultado = $conexao->query($sql);

if (!$resultado) {
  http_response_code(500);
  echo json_encode([
    "sucesso" => false,
    "mensagem" => "Erro ao consultar coordenadores",
    "erro" => $conexao->error
  ]);
  exit;
}

$dados = [];
while ($linha = $resultado->fetch_assoc()) {
  $linha["email"] = $linha["email"] ?? "";
  $linha["telefone"] = $linha["telefone"] ?? "";
  $linha["departamento"] = $linha["departamento"] ?? "";
  $linha["formacao"] = $linha["formacao"] ?? "";
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