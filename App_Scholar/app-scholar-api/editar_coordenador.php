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
$nome = trim($dados["nome"] ?? "");
$email = trim($dados["email"] ?? "");
$telefone = trim($dados["telefone"] ?? "");
$departamento = trim($dados["departamento"] ?? "");
$dataAdmissao = trim($dados["dataAdmissao"] ?? "");
$formacao = trim($dados["formacao"] ?? "");
$observacoes = trim($dados["observacoes"] ?? "");

if (
  $id <= 0 || $nome === "" || $email === "" || $telefone === "" ||
  $departamento === "" || $dataAdmissao === ""
) {
  http_response_code(400);
  echo json_encode(["sucesso" => false, "mensagem" => "Dados obrigatórios incompletos"]);
  exit;
}

if (!preg_match('/^\d{11}$/', $telefone)) {
  echo json_encode(["sucesso" => false, "mensagem" => "O telefone deve possuir 11 números"]);
  exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  echo json_encode(["sucesso" => false, "mensagem" => "E-mail inválido"]);
  exit;
}

$partes = explode("/", $dataAdmissao);
if (count($partes) !== 3) {
  echo json_encode(["sucesso" => false, "mensagem" => "Data de admissão inválida"]);
  exit;
}

$dia = intval($partes[0]);
$mes = intval($partes[1]);
$ano = intval($partes[2]);

if (!checkdate($mes, $dia, $ano)) {
  echo json_encode(["sucesso" => false, "mensagem" => "Data de admissão inválida"]);
  exit;
}

$dataBanco = sprintf("%04d-%02d-%02d", $ano, $mes, $dia);

$idFormacao = null;
if ($formacao !== "") {
  $stmt = $conexao->prepare("
    SELECT id_formacoes FROM formacoes
    WHERE Formacao = ?
    LIMIT 1
  ");
  $stmt->bind_param("s", $formacao);
  $stmt->execute();
  $res = $stmt->get_result();

  if ($res->num_rows === 0) {
    echo json_encode(["sucesso" => false, "mensagem" => "Formação não encontrada no banco"]);
    exit;
  }

  $idFormacao = intval($res->fetch_assoc()["id_formacoes"]);
  $stmt->close();
}

$conexao->begin_transaction();

try {
  $stmt = $conexao->prepare("
    SELECT id_contatos
    FROM coordenadores
    WHERE id_coordenadores = ?
      AND STATUS = 'A'
    LIMIT 1
    FOR UPDATE
  ");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $res = $stmt->get_result();

  if ($res->num_rows === 0) {
    throw new Exception("Coordenador não encontrado ou inativo");
  }

  $idContato = $res->fetch_assoc()["id_contatos"];
  $stmt->close();

  $stmt = $conexao->prepare("
    UPDATE coordenadores
    SET
      Nome = ?,
      Departamento = ?,
      DataAdmissao = ?,
      Observacoes = ?,
      id_formacoes = ?
    WHERE id_coordenadores = ?
      AND STATUS = 'A'
  ");
  $stmt->bind_param(
    "ssssii",
    $nome,
    $departamento,
    $dataBanco,
    $observacoes,
    $idFormacao,
    $id
  );
  $stmt->execute();
  $stmt->close();

  if ($idContato) {
    $stmt = $conexao->prepare("
      UPDATE contatos
      SET Telefone = ?, Email = ?
      WHERE id_contatos = ?
    ");
    $stmt->bind_param("ssi", $telefone, $email, $idContato);
    $stmt->execute();
    $stmt->close();
  } else {
    $stmt = $conexao->prepare("
      INSERT INTO contatos (Telefone, Email)
      VALUES (?, ?)
    ");
    $stmt->bind_param("ss", $telefone, $email);
    $stmt->execute();
    $novoContato = $conexao->insert_id;
    $stmt->close();

    $stmt = $conexao->prepare("
      UPDATE coordenadores
      SET id_contatos = ?
      WHERE id_coordenadores = ?
    ");
    $stmt->bind_param("ii", $novoContato, $id);
    $stmt->execute();
    $stmt->close();
  }

  $conexao->commit();

  echo json_encode([
    "sucesso" => true,
    "mensagem" => "Coordenador atualizado com sucesso"
  ]);
} catch (Throwable $erro) {
  $conexao->rollback();
  http_response_code(500);
  echo json_encode([
    "sucesso" => false,
    "mensagem" => "Erro ao atualizar coordenador",
    "erro" => $erro->getMessage()
  ]);
}

$conexao->close();
?>