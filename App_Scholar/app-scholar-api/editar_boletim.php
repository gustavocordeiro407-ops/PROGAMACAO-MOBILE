<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") exit;
require_once "conexao.php";

if ($_SERVER["REQUEST_METHOD"] !== "PUT") {
  http_response_code(405);
  echo json_encode(["sucesso" => false, "mensagem" => "Método não permitido"]);
  exit;
}

$dados = json_decode(file_get_contents("php://input"), true);

$id = intval($dados["id"] ?? 0);
$aluno = trim($dados["aluno"] ?? "");
$turma = trim($dados["turma"] ?? "");
$periodo = trim($dados["periodo"] ?? "");
$dataEmissao = trim($dados["dataEmissao"] ?? "");
$mediaFinal = floatval($dados["mediaFinal"] ?? -1);
$frequencia = floatval($dados["frequencia"] ?? -1);
$situacaoFinal = trim($dados["situacaoFinal"] ?? "");
$observacoes = trim($dados["observacoes"] ?? "");

if (
  $id <= 0 || $aluno === "" || $turma === "" ||
  $periodo === "" || $dataEmissao === "" ||
  $mediaFinal < 0 || $frequencia < 0 ||
  $situacaoFinal === ""
) {
  http_response_code(400);
  echo json_encode(["sucesso" => false, "mensagem" => "Dados incompletos ou inválidos"]);
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
  echo json_encode(["sucesso" => false, "mensagem" => "Matrícula ativa não encontrada ou duplicada"]);
  exit;
}

$idMatricula = intval($res->fetch_assoc()["id_matriculas"]);
$stmt->close();

$stmt = $conexao->prepare("
  SELECT id_boletins
  FROM boletins
  WHERE id_matriculas = ?
    AND Periodo = ?
    AND id_boletins <> ?
    AND STATUS = 'A'
  LIMIT 1
");
$stmt->bind_param("isi", $idMatricula, $periodo, $id);
$stmt->execute();
$resDuplicado = $stmt->get_result();

if ($resDuplicado->num_rows > 0) {
  echo json_encode(["sucesso" => false, "mensagem" => "Já existe outro boletim ativo para esse período"]);
  exit;
}
$stmt->close();

$stmt = $conexao->prepare("
  UPDATE boletins
  SET
    Periodo = ?,
    DataEmissao = ?,
    MediaFinal = ?,
    SituacaoFinal = ?,
    Frequencia = ?,
    Observacoes = ?,
    id_matriculas = ?
  WHERE id_boletins = ?
    AND STATUS = 'A'
");
$stmt->bind_param(
  "ssdsdsii",
  $periodo,
  $dataBanco,
  $mediaFinal,
  $situacaoFinal,
  $frequencia,
  $observacoes,
  $idMatricula,
  $id
);
$stmt->execute();

echo json_encode([
  "sucesso" => true,
  "mensagem" => "Boletim atualizado com sucesso"
]);

$stmt->close();
$conexao->close();
?>