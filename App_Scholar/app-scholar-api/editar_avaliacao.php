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
$disciplina = trim($dados["disciplina"] ?? "");
$tipo = trim($dados["tipo"] ?? "");
$titulo = trim($dados["titulo"] ?? "");
$dataAvaliacao = trim($dados["dataAvaliacao"] ?? "");
$valorMaximo = floatval($dados["valorMaximo"] ?? 0);
$notaObtida = floatval($dados["notaObtida"] ?? -1);
$observacoes = trim($dados["observacoes"] ?? "");

if (
  $id <= 0 || $aluno === "" || $disciplina === "" || $tipo === "" ||
  $titulo === "" || $dataAvaliacao === "" ||
  $valorMaximo <= 0 || $notaObtida < 0
) {
  http_response_code(400);
  echo json_encode(["sucesso" => false, "mensagem" => "Dados incompletos ou inválidos"]);
  exit;
}

if ($notaObtida > $valorMaximo) {
  echo json_encode(["sucesso" => false, "mensagem" => "A nota obtida não pode ser maior que o valor máximo"]);
  exit;
}

$partes = explode("/", $dataAvaliacao);
if (count($partes) !== 3) {
  echo json_encode(["sucesso" => false, "mensagem" => "Data inválida"]);
  exit;
}

$dia = intval($partes[0]);
$mes = intval($partes[1]);
$ano = intval($partes[2]);

if (!checkdate($mes, $dia, $ano)) {
  echo json_encode(["sucesso" => false, "mensagem" => "Data inválida"]);
  exit;
}
$dataBanco = sprintf("%04d-%02d-%02d", $ano, $mes, $dia);

$stmt = $conexao->prepare("
  SELECT id_alunos FROM alunos
  WHERE Nome = ? AND STATUS = 'A'
");
$stmt->bind_param("s", $aluno);
$stmt->execute();
$resAluno = $stmt->get_result();
if ($resAluno->num_rows !== 1) {
  echo json_encode(["sucesso" => false, "mensagem" => "Aluno não encontrado ou nome duplicado"]);
  exit;
}
$idAluno = intval($resAluno->fetch_assoc()["id_alunos"]);
$stmt->close();

$stmt = $conexao->prepare("
  SELECT id_disciplinas FROM disciplinas
  WHERE Nome = ? AND STATUS = 'A'
");
$stmt->bind_param("s", $disciplina);
$stmt->execute();
$resDisc = $stmt->get_result();
if ($resDisc->num_rows !== 1) {
  echo json_encode(["sucesso" => false, "mensagem" => "Disciplina não encontrada ou nome duplicado"]);
  exit;
}
$idDisciplina = intval($resDisc->fetch_assoc()["id_disciplinas"]);
$stmt->close();

$conexao->begin_transaction();

try {
  $stmt = $conexao->prepare("
    UPDATE avaliacoes
    SET DataDaAvaliacao = ?, Tipo = ?, Titulo = ?, ValorMaximo = ?,
        Observacoes = ?, id_alunos = ?, id_disciplinas = ?
    WHERE id_avaliacoes = ?
      AND STATUS = 'A'
  ");
  $stmt->bind_param(
    "sssdsiii",
    $dataBanco, $tipo, $titulo, $valorMaximo, $observacoes,
    $idAluno, $idDisciplina, $id
  );
  $stmt->execute();
  $stmt->close();

  $stmt = $conexao->prepare("
    SELECT id_notas
    FROM notas
    WHERE id_avaliacoes = ?
    ORDER BY id_notas DESC
    LIMIT 1
  ");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $resNota = $stmt->get_result();

  if ($resNota->num_rows > 0) {
    $idNota = intval($resNota->fetch_assoc()["id_notas"]);
    $stmt->close();

    $stmt = $conexao->prepare("
      UPDATE notas
      SET NotaObtida = ?, id_alunos = ?
      WHERE id_notas = ?
    ");
    $stmt->bind_param("dii", $notaObtida, $idAluno, $idNota);
    $stmt->execute();
    $stmt->close();
  } else {
    $stmt->close();

    $stmt = $conexao->prepare("
      INSERT INTO notas (NotaObtida, id_alunos, id_avaliacoes)
      VALUES (?, ?, ?)
    ");
    $stmt->bind_param("dii", $notaObtida, $idAluno, $id);
    $stmt->execute();
    $stmt->close();
  }

  $conexao->commit();
  echo json_encode(["sucesso" => true, "mensagem" => "Avaliação atualizada com sucesso"]);

} catch (Throwable $erro) {
  $conexao->rollback();
  http_response_code(500);
  echo json_encode([
    "sucesso" => false,
    "mensagem" => "Erro ao atualizar avaliação",
    "erro" => $erro->getMessage()
  ]);
}

$conexao->close();
?>