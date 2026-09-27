<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PATCH, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") exit;
require_once "conexao.php";

if ($_SERVER["REQUEST_METHOD"] !== "PATCH") {
  http_response_code(405);
  echo json_encode(["sucesso" => false, "mensagem" => "Método não permitido"]);
  exit;
}

$dados = json_decode(file_get_contents("php://input"), true);
$id = intval($dados["id"] ?? 0);

if ($id <= 0) {
  http_response_code(400);
  echo json_encode(["sucesso" => false, "mensagem" => "ID da avaliação não informado"]);
  exit;
}

$stmt = $conexao->prepare("
  UPDATE avaliacoes
  SET STATUS = 'I'
  WHERE id_avaliacoes = ?
    AND STATUS = 'A'
");
$stmt->bind_param("i", $id);
$stmt->execute();

echo json_encode([
  "sucesso" => $stmt->affected_rows > 0,
  "mensagem" => $stmt->affected_rows > 0
    ? "Avaliação inativada com sucesso"
    : "Avaliação não encontrada ou já está inativa"
]);

$stmt->close();
$conexao->close();
?>