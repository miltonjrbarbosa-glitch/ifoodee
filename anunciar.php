<?php
require 'config.php';

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. DADOS DE ACESSO E PERFIL
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $idade = (int)$_POST['idade'];
    $estado = $_POST['estado'];
    $cidade = $_POST['cidade'];
    $bairro = $_POST['bairro'];
    $whatsapp = preg_replace('/[^0-9]/', '', $_POST['whatsapp']);
    $descricao = $_POST['descricao'];
    $valor = (float)str_replace(['.', ','], ['', '.'], $_POST['valor']);
    $latitude = $_POST['latitude'] ?? 0;
    $longitude = $_POST['longitude'] ?? 0;

    // 2. DADOS SIGILOSOS (DOCUMENTAÇÃO)
    $cpf = $_POST['cpf'];
    $rg = $_POST['rg'];
    
    // Tratamento das Checkboxes
    $caracteristicas = isset($_POST['caracteristicas']) ? implode(',', $_POST['caracteristicas']) : '';
    $servicos = isset($_POST['servicos']) ? implode(',', $_POST['servicos']) : '';
    $locais = isset($_POST['locais']) ? implode(',', $_POST['locais']) : '';
    $pagamentos = isset($_POST['pagamentos']) ? implode(',', $_POST['pagamentos']) : '';

    // 3. UPLOAD DAS FOTOS DE DOCUMENTAÇÃO
    $nome_doc = '';
    $nome_selfie = '';

    // Foto do Documento
    if (isset($_FILES['foto_documento']) && $_FILES['foto_documento']['error'] == 0) {
        $ext = pathinfo($_FILES['foto_documento']['name'], PATHINFO_EXTENSION);
        $nome_doc = uniqid('doc_') . '.' . $ext;
        move_uploaded_file($_FILES['foto_documento']['tmp_name'], 'uploads/' . $nome_doc);
    }
    
    // Selfie com Documento
    if (isset($_FILES['foto_selfie_doc']) && $_FILES['foto_selfie_doc']['error'] == 0) {
        $ext = pathinfo($_FILES['foto_selfie_doc']['name'], PATHINFO_EXTENSION);
        $nome_selfie = uniqid('selfie_') . '.' . $ext;
        move_uploaded_file($_FILES['foto_selfie_doc']['tmp_name'], 'uploads/' . $nome_selfie);
    }

    // 4. UPLOAD DA CAPA E INSERÇÃO NO BANCO
    if (isset($_FILES['foto_capa']) && $_FILES['foto_capa']['error'] == 0) {
        $ext = pathinfo($_FILES['foto_capa']['name'], PATHINFO_EXTENSION);
        $nome_capa = uniqid('capa_') . '.' . $ext;
        
        if (move_uploaded_file($_FILES['foto_capa']['tmp_name'], 'uploads/' . $nome_capa)) {
            try {
                $pdo->beginTransaction();

                // SQL GIGANTE COM TUDO INCLUINDO DOCUMENTOS
                $sql = "INSERT INTO perfis (
                    nome, email, senha, idade, estado, cidade, bairro, whatsapp, descricao, foto, valor, latitude, longitude, 
                    servicos, locais, pagamentos, caracteristicas, 
                    cpf, rg, foto_documento, foto_selfie_doc
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $nome, $email, $senha, $idade, $estado, $cidade, $bairro, $whatsapp, $descricao, $nome_capa, $valor, $latitude, $longitude,
                    $servicos, $locais, $pagamentos, $caracteristicas,
                    $cpf, $rg, $nome_doc, $nome_selfie
                ]);
                
                $perfil_id = $pdo->lastInsertId();

                // 5. UPLOAD GALERIA
                if (isset($_FILES['fotos_extras'])) {
                    $total = count($_FILES['fotos_extras']['name']);
                    for ($i = 0; $i < $total; $i++) {
                        if ($i >= 10) break; 
                        if ($_FILES['fotos_extras']['error'][$i] == 0) {
                            $ext = pathinfo($_FILES['fotos_extras']['name'][$i], PATHINFO_EXTENSION);
                            $nome_arq = uniqid('foto_') . '.' . $ext;
                            move_uploaded_file($_FILES['fotos_extras']['tmp_name'][$i], 'uploads/' . $nome_arq);
                            $pdo->prepare("INSERT INTO fotos_galeria (perfil_id, arquivo) VALUES (?, ?)")->execute([$perfil_id, $nome_arq]);
                        }
                    }
                }

                // 6. UPLOAD VÍDEOS
                if (isset($_FILES['videos'])) {
                    $total_v = count($_FILES['videos']['name']);
                    for ($i = 0; $i < $total_v; $i++) {
                        if ($i >= 2) break;
                        if ($_FILES['videos']['error'][$i] == 0) {
                            $ext = pathinfo($_FILES['videos']['name'][$i], PATHINFO_EXTENSION);
                            if(in_array(strtolower($ext), ['mp4', 'mov', 'avi'])) {
                                $nome_vid = uniqid('video_') . '.' . $ext;
                                move_uploaded_file($_FILES['videos']['tmp_name'][$i], 'uploads/' . $nome_vid);
                                $pdo->prepare("INSERT INTO videos_galeria (perfil_id, arquivo) VALUES (?, ?)")->execute([$perfil_id, $nome_vid]);
                            }
                        }
                    }
                }

                $pdo->commit();
                $mensagem = "Cadastro enviado para análise! <a href='login.php'>Fazer Login</a>";

            } catch (Exception $e) {
                $pdo->rollBack();
                $erro = "Erro: " . $e->getMessage();
            }
        }
    } else {
        $erro = "Foto de capa obrigatória.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro com Verificação</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Roboto', sans-serif; background: #f4f6f8; margin: 0; }
        .container { max-width: 800px; margin: 30px auto; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        
        h2 { text-align: center; color: #333; }
        .section-title { font-weight: bold; color: #c62828; margin-top: 30px; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px; font-size: 1.1rem; }
        
        input, select, textarea { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }

        /* Estilo da área de Documentação */
        .doc-box { background: #e8f0fe; border: 1px solid #b3d7ff; padding: 20px; border-radius: 6px; margin-top: 20px; }
        .doc-title { color: #0d47a1; font-weight: bold; margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
        .doc-alert { font-size: 0.85rem; color: #555; margin-bottom: 15px; }

        .checkbox-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
        @media(min-width: 600px) { .checkbox-grid { grid-template-columns: repeat(3, 1fr); } }
        .checkbox-item { display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: #555; cursor: pointer; }
        .checkbox-item input { width: auto; margin: 0; }

        button { width: 100%; background: #c62828; color: white; padding: 15px; border: none; font-weight: bold; cursor: pointer; font-size: 1.1rem; border-radius: 4px; margin-top: 30px; }
        button:hover { background: #b71c1c; }
        
        .msg { padding: 15px; background: #d4edda; color: #155724; text-align: center; margin-bottom: 20px; border-radius: 4px; }
        .err { padding: 15px; background: #f8d7da; color: #721c24; text-align: center; margin-bottom: 20px; border-radius: 4px; }
    </style>
</head>
<body>
    <?php include 'topo.php'; ?>
    
    <div class="container">
        <h2>Criar Anúncio</h2>
        
        <?php if($mensagem) echo "<div class='msg'>$mensagem</div>"; ?>
        <?php if($erro) echo "<div class='err'>$erro</div>"; ?>

        <form method="POST" enctype="multipart/form-data">
            
            <!-- ÁREA DE SEGURANÇA / DOCUMENTAÇÃO -->
            <div class="doc-box">
                <div class="doc-title"><i class="fas fa-lock"></i> Verificação de Identidade (Sigiloso)</div>
                <div class="doc-alert">
                    <i class="fas fa-info-circle"></i> 
                    Seus documentos, CPF e RG <strong>NÃO aparecerão no site</strong>. Eles servem apenas para verificarmos sua idade e identidade.
                </div>

                <div style="display:flex; gap:10px;">
                    <div style="flex:1">
                        <label>CPF:</label>
                        <input type="text" name="cpf" placeholder="000.000.000-00" required>
                    </div>
                    <div style="flex:1">
                        <label>RG / Identidade:</label>
                        <input type="text" name="rg" placeholder="Número do RG" required>
                    </div>
                </div>

                <label><strong>1. Foto do Documento (Frente/Verso):</strong></label>
                <input type="file" name="foto_documento" required accept="image/*">
                
                <label><strong>2. Selfie segurando o documento ao lado do rosto:</strong></label>
                <input type="file" name="foto_selfie_doc" required accept="image/*">
            </div>

            <!-- DADOS DO PERFIL (PÚBLICO) -->
            <div class="section-title">Dados de Acesso</div>
            <input type="email" name="email" placeholder="Seu E-mail (Login)" required>
            <input type="password" name="senha" placeholder="Crie uma Senha" required>

            <div class="section-title">Informações do Perfil</div>
            <input type="text" name="nome" placeholder="Nome Artístico" required>
            <div style="display:flex; gap:10px;">
                <input type="number" name="idade" placeholder="Idade" required>
                <input type="text" name="valor" placeholder="Cachê (R$)" required>
            </div>
            <input type="text" name="whatsapp" placeholder="WhatsApp (DDD + Número)" required>

            <label style="font-weight:bold; display:block; margin-bottom:10px;">Suas Características:</label>
            <div class="checkbox-grid">
                <label class="checkbox-item"><input type="checkbox" name="caracteristicas[]" value="Branca"> Branca</label>
                <label class="checkbox-item"><input type="checkbox" name="caracteristicas[]" value="Morena"> Morena</label>
                <label class="checkbox-item"><input type="checkbox" name="caracteristicas[]" value="Negra"> Negra</label>
                <label class="checkbox-item"><input type="checkbox" name="caracteristicas[]" value="Loira"> Loira</label>
                <label class="checkbox-item"><input type="checkbox" name="caracteristicas[]" value="Magra"> Magra</label>
                <label class="checkbox-item"><input type="checkbox" name="caracteristicas[]" value="BBW"> BBW / Gordinha</label>
                <label class="checkbox-item"><input type="checkbox" name="caracteristicas[]" value="Tatuada"> Tatuada</label>
                <label class="checkbox-item"><input type="checkbox" name="caracteristicas[]" value="Peituda"> Peituda</label>
            </div>

            <div class="section-title">Localização</div>
            <div style="display:flex; gap:10px;">
                <select name="estado" style="width:100px"><option value="SP">SP</option><option value="MG">MG</option><option value="RJ">RJ</option><option value="RS">RS</option><option value="PR">PR</option></select>
                <input type="text" name="cidade" placeholder="Cidade" required>
            </div>
            <input type="text" name="bairro" placeholder="Bairro" required>

            <div class="section-title">O que faz e Onde atende</div>
            
            <label style="font-weight:bold; display:block; margin:10px 0;">Locais:</label>
            <div class="checkbox-grid">
                <label class="checkbox-item"><input type="checkbox" name="locais[]" value="Com local"> Com local</label>
                <label class="checkbox-item"><input type="checkbox" name="locais[]" value="Motel"> Motel</label>
                <label class="checkbox-item"><input type="checkbox" name="locais[]" value="Hotel"> Hotel</label>
                <label class="checkbox-item"><input type="checkbox" name="locais[]" value="À domicílio"> À domicílio</label>
            </div>

            <label style="font-weight:bold; display:block; margin:15px 0 10px 0;">Serviços:</label>
            <div class="checkbox-grid">
                <label class="checkbox-item"><input type="checkbox" name="servicos[]" value="Beijo na boca"> Beijo na boca</label>
                <label class="checkbox-item"><input type="checkbox" name="servicos[]" value="Massagem Erótica"> Massagem</label>
                <label class="checkbox-item"><input type="checkbox" name="servicos[]" value="Namoradinha"> Namoradinha</label>
                <label class="checkbox-item"><input type="checkbox" name="servicos[]" value="Oral Completo"> Oral Completo</label>
                <label class="checkbox-item"><input type="checkbox" name="servicos[]" value="Anal"> Anal</label>
                <label class="checkbox-item"><input type="checkbox" name="servicos[]" value="Casal"> Casal</label>
            </div>

            <div class="section-title">Pagamento</div>
            <div class="checkbox-grid">
                <label class="checkbox-item"><input type="checkbox" name="pagamentos[]" value="Dinheiro"> Dinheiro</label>
                <label class="checkbox-item"><input type="checkbox" name="pagamentos[]" value="PIX"> PIX</label>
                <label class="checkbox-item"><input type="checkbox" name="pagamentos[]" value="Cartão"> Cartão</label>
            </div>

            <div class="section-title">Descrição e Mídia Pública</div>
            <textarea name="descricao" placeholder="Escreva mais sobre você..." rows="4"></textarea>

            <label><strong>Foto de Capa (Pública):</strong></label>
            <input type="file" name="foto_capa" required accept="image/*">

            <label><strong>Galeria (Até 10 fotos):</strong></label>
            <input type="file" name="fotos_extras[]" multiple accept="image/*">
            
            <label><strong>Vídeos (Até 2):</strong></label>
            <input type="file" name="videos[]" multiple accept="video/*">

            <button type="submit">ENVIAR CADASTRO PARA ANÁLISE</button>
        </form>
    </div>
</body>
</html>
