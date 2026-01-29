<?php
session_start();
require 'config.php';

// Verifica se está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_SESSION['user_id'];
$msg = '';

// SE CLICOU EM SALVAR ALTERAÇÕES
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $idade = $_POST['idade'];
    $valor = str_replace(['.', ','], ['', '.'], $_POST['valor']);
    $cidade = $_POST['cidade'];
    $whatsapp = preg_replace('/[^0-9]/', '', $_POST['whatsapp']);
    $descricao = $_POST['descricao'];

    // Se enviou foto nova
    if (!empty($_FILES['foto']['name'])) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $novo_nome = uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/' . $novo_nome);
        
        // Atualiza com foto
        $sql = "UPDATE perfis SET nome=?, idade=?, valor=?, cidade=?, whatsapp=?, descricao=?, foto=? WHERE id=?";
        $pdo->prepare($sql)->execute([$nome, $idade, $valor, $cidade, $whatsapp, $descricao, $novo_nome, $id]);
    } else {
        // Atualiza SEM mudar a foto
        $sql = "UPDATE perfis SET nome=?, idade=?, valor=?, cidade=?, whatsapp=?, descricao=? WHERE id=?";
        $pdo->prepare($sql)->execute([$nome, $idade, $valor, $cidade, $whatsapp, $descricao, $id]);
    }
    $msg = "Dados atualizados com sucesso!";
}

// Busca os dados atuais para preencher o formulário
$stmt = $pdo->prepare("SELECT * FROM perfis WHERE id = ?");
$stmt->execute([$id]);
$perfil = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel - Editar Perfil</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Roboto', sans-serif; background: #f2f2f2; margin: 0; padding: 20px; }
        .painel { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .btn-sair { color: red; text-decoration: none; font-weight: bold; }
        input, textarea { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background: #007bff; color: white; padding: 15px 30px; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; }
        .msg-ok { background: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; text-align: center; }
        img.preview { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; border: 2px solid #ddd; display: block; margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="painel">
    <div class="header">
        <h2>Olá, <?= htmlspecialchars($perfil['nome']) ?></h2>
        <div>
            <a href="perfil.php?id=<?= $id ?>" target="_blank" style="margin-right: 15px;">Ver meu perfil</a>
            <a href="logout.php" class="btn-sair">Sair</a>
        </div>
    </div>

    <?php if($msg) echo "<div class='msg-ok'>$msg</div>"; ?>

    <form method="POST" enctype="multipart/form-data">
        <label>Nome:</label>
        <input type="text" name="nome" value="<?= htmlspecialchars($perfil['nome']) ?>">

        <div style="display:flex; gap: 10px;">
            <div style="flex:1">
                <label>Idade:</label>
                <input type="number" name="idade" value="<?= $perfil['idade'] ?>">
            </div>
            <div style="flex:1">
                <label>Valor (R$):</label>
                <input type="text" name="valor" value="<?= number_format($perfil['valor'], 2, ',', '.') ?>">
            </div>
        </div>

        <label>Cidade:</label>
        <input type="text" name="cidade" value="<?= htmlspecialchars($perfil['cidade']) ?>">

        <label>WhatsApp:</label>
        <input type="text" name="whatsapp" value="<?= htmlspecialchars($perfil['whatsapp']) ?>">

        <label>Descrição:</label>
        <textarea name="descricao" rows="5"><?= htmlspecialchars($perfil['descricao']) ?></textarea>

        <label>Alterar Foto (Deixe em branco para manter a atual):</label>
        <?php if($perfil['foto']) echo "<img src='uploads/{$perfil['foto']}' class='preview'>"; ?>
        <input type="file" name="foto">

        <button type="submit">SALVAR ALTERAÇÕES</button>
    </form>
</div>

</body>
</html>
