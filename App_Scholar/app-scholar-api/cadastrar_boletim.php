<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") exit;
require_once "conexao.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  echo json_encode(["sucesso" => false, "mensagem" => "Método não permitido"]);
  exit;
}

$dados = json_decode(file_get_contents("php://input"), true);

$aluno = trim($dados["aluno"] ?? "");
$turma = trim($dados["turma"] ?? "");
$periodo = trim($dados["periodo"] ?? "");
$dataEmissao = trim($dados["dataEmissao"] ?? "");
$mediaFinal = floatval($dados["mediaFinal"] ?? -1);
$frequencia = floatval($dados["frequencia"] ?? -1);
$situacaoFinal = trim($dados["situacaoFinal"] ?? "");
$observacoes = trim($dados["observacoes"] ?? "");

if (
  $aluno === "" || $turma === "" || $periodo === "" ||
  $dataEmissao === "" || $mediaFinal < 0 ||
  $frequencia < 0 || $situacaoFinal === ""
) {
  http_response_code(400);
  echo json_encode(["sucesso" => false, "mensagem" => "Preencha todos os campos obrigatórios corretamente"]);
  exit;
}

if ($mediaFinal > 10) {
  echo json_encode(["sucesso" => false, "mensagem" => "A média final deve estar entre 0 e 10"]);
  exit;
}

if ($frequencia > 100) {
  echo json_encode(["sucesso" => false, "mensagem" => "A frequência deve estar entre 0 e 100"]);
  exit;
}

$partes = explode("/", $dataEmissao);
if (count($partes) !== 3) {
  echo json_encode(["sucesso" => false, "mensagem" => "Data de emissão inválida"]);
  exit;
}

$dia = intval($partes[0]);
$mes = intval($partes[1]);
$ano = intval($partes[2]);

if (!checkdate($mes, $dia, $ano)) {
  echo json_encode(["sucesso" => false, "mensagem" => "Data de emissão inválida"]);
  exit;
}

$dataBanco = sprintf("%04d-%02d-%02d", $ano, $mes, $dia);

$stmt = $conexao->prepare("
  SELECT m.id_matriculas
  FROM matriculas m
  INNER JOIN alunos a
    ON m.id_alunos = a.id_alunos
  INNER JOIN turmas t
    ON m.id_turmas = t.id_turmas
  WHERE a.Nome = ?
    AND t.Sala = ?
    AND m.STATUS = 'A'
    AND a.STATUS = 'A'
    AND t.STATUS = 'A'
");
$stmt->bind_param("ss", $aluno, $turma);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows !== 1) {
  echo json_encode(["sucesso" => false, "mensagem" => "Matrícula ativa não encontrada ou duplicada para esse aluno e turma"]);
  exit;
}

$idMatricula = intval($res->fetch_assoc()["id_matriculas"]);
$stmt->close();

$stmt = $conexao->prepare("
  SELECT id_boletins
  FROM boletins
  WHERE id_matriculas = ?
    AND Periodo = ?
    AND STATUS = 'A'
  LIMIT 1
");
$stmt->bind_param("is", $idMatricula, $periodo);
$stmt->execute();
$resDuplicado = $stmt->get_result();

if ($resDuplicado->num_rows > 0) {
  echo json_encode(["sucesso" => false, "mensagem" => "Já existe um boletim ativo para esse período"]);
  exit;
}
$stmt->close();

$stmt = $conexao->prepare("
  INSERT INTO boletins
  (
    Periodo, DataEmissao, MediaFinal, SituacaoFinal,
    Frequencia, Observacoes, id_matriculas, STATUS
  )
  VALUES (?, ?, ?, ?, ?, ?, ?, 'A')
");
$stmt->bind_param(
  "ssdsdsi",
  $periodo,
  $dataBanco,
  $mediaFinal,
  $situacaoFinal,
  $frequencia,
  $observacoes,
  $idMatricula
);
$stmt->execute();

echo json_encode([
  "sucesso" => true,
  "mensagem" => "Boletim cadastrado com sucesso",
  "id" => $conexao->insert_id
]);

$stmt->close();
$conexao->close();
?>