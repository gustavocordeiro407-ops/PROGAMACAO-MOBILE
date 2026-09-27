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

$nome = trim($dados["nome"] ?? "");
$email = trim($dados["email"] ?? "");
$telefone = trim($dados["telefone"] ?? "");
$departamento = trim($dados["departamento"] ?? "");
$dataAdmissao = trim($dados["dataAdmissao"] ?? "");
$formacao = trim($dados["formacao"] ?? "");
$observacoes = trim($dados["observacoes"] ?? "");

if (
  $nome === "" || $email === "" || $telefone === "" ||
  $departamento === "" || $dataAdmissao === ""
) {
  http_response_code(400);
  echo json_encode(["sucesso" => false, "mensagem" => "Preencha todos os campos obrigatórios"]);
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
    SELECT id_formacoes
    FROM formacoes
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
    INSERT INTO contatos (Telefone, Email)
    VALUES (?, ?)
  ");
  $stmt->bind_param("ss", $telefone, $email);
  $stmt->execute();
  $idContato = $conexao->insert_id;
  $stmt->close();

  $stmt = $conexao->prepare("
    INSERT INTO coordenadores
    (
      Nome, CPF, Departamento, DataAdmissao,
      Observacoes, id_contatos, id_formacoes, STATUS
    )
    VALUES (?, NULL, ?, ?, ?, ?, ?, 'A')
  ");
  $stmt->bind_param(
    "ssssii",
    $nome,
    $departamento,
    $dataBanco,
    $observacoes,
    $idContato,
    $idFormacao
  );
  $stmt->execute();
  $idCoordenador = $conexao->insert_id;
  $stmt->close();

  $conexao->commit();

  echo json_encode([
    "sucesso" => true,
    "mensagem" => "Coordenador cadastrado com sucesso",
    "id" => $idCoordenador
  ]);
} catch (Throwable $erro) {
  $conexao->rollback();
  http_response_code(500);
  echo json_encode([
    "sucesso" => false,
    "mensagem" => "Erro ao cadastrar coordenador",
    "erro" => $erro->getMessage()
  ]);
}

$conexao->close();
?>