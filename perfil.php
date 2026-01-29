<?php
require 'config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header("Location: index.php"); exit; }

// Busca dados do perfil
$stmt = $pdo->prepare("SELECT * FROM perfis WHERE id = :id");
$stmt->execute([':id' => $id]);
$perfil = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$perfil) { header("Location: index.php"); exit; }

// Busca Fotos da Galeria
$stmtFotos = $pdo->prepare("SELECT * FROM fotos_galeria WHERE perfil_id = :id");
$stmtFotos->execute([':id' => $id]);
$galeria = $stmtFotos->fetchAll(PDO::FETCH_ASSOC);

// Busca Vídeos
$stmtVideos = $pdo->prepare("SELECT * FROM videos_galeria WHERE perfil_id = :id");
$stmtVideos->execute([':id' => $id]);
$videos = $stmtVideos->fetchAll(PDO::FETCH_ASSOC);

// Tratamento do WhatsApp e Endereço para o Mapa
$whats_limpo = preg_replace('/[^0-9]/', '', $perfil['whatsapp']);
$endereco_mapa = urlencode($perfil['bairro'] . ', ' . $perfil['cidade'] . ' - ' . $perfil['estado']);

// --- FUNÇÃO PARA GERAR AS TAGS (Simulação se estiver vazio) ---
// Se o banco estiver vazio nessas colunas, vou colocar dados de exemplo para você ver o layout funcionando.
// Depois você conecta isso ao formulário de anunciar.
$servicos_lista = !empty($perfil['servicos']) ? explode(',', $perfil['servicos']) : ['Beijo na boca', 'Massagem', 'Namoradinha', 'Fetiches'];
$locais_lista = !empty($perfil['locais']) ? explode(',', $perfil['locais']) : ['Motel', 'Hotel', 'Viagens', 'Jantar'];
$pagamentos_lista = !empty($perfil['pagamentos']) ? explode(',', $perfil['pagamentos']) : ['Dinheiro', 'Pix', 'Cartão'];
$carac_lista = !empty($perfil['caracteristicas']) ? explode(',', $perfil['caracteristicas']) : ['Morena', 'Magra', 'Tatuada'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($perfil['nome']) ?> - <?= htmlspecialchars($perfil['cidade']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        * { box-sizing: border-box; }
        body { background-color: #f4f6f8; font-family: 'Roboto', sans-serif; margin: 0; padding: 0; color: #333; }
        
        .container { max-width: 1100px; margin: 20px auto; padding: 0 15px; }

        /* HEADER E BREADCRUMBS */
        .breadcrumbs { font-size: 0.9rem; color: #666; margin-bottom: 15px; }
        .breadcrumbs a { text-decoration: none; color: #666; }
        .breadcrumbs a:hover { color: #c62828; }

        /* CARTÃO PRINCIPAL (IDÊNTICO AO ANTERIOR) */
        .profile-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; display: flex; flex-direction: column; margin-bottom: 20px; }
        @media(min-width: 768px) { .profile-card { flex-direction: row; } }

        .profile-left { width: 100%; max-width: 400px; position: relative; }
        .main-photo { width: 100%; height: 100%; min-height: 400px; object-fit: cover; display: block; }
        
        .profile-right { padding: 30px; flex: 1; }
        .profile-name { font-size: 2.5rem; font-weight: 700; color: #333; margin: 0; }
        .profile-meta { font-size: 1.1rem; color: #666; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        
        .btn-whatsapp { 
            background: #25D366; color: white; text-decoration: none; font-weight: bold; font-size: 1.2rem; 
            padding: 15px; border-radius: 50px; display: flex; align-items: center; justify-content: center; gap: 10px; 
            transition: 0.3s; box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3); margin-bottom: 30px;
        }
        .btn-whatsapp:hover { background: #1ebc57; transform: translateY(-2px); }

        .descricao-texto { font-size: 1rem; line-height: 1.6; color: #555; margin-bottom: 30px; border-left: 4px solid #c62828; padding-left: 15px; }

        /* --- ESTILO DAS TAGS (ETIQUETAS AZUIS) IGUAL AO PRINT --- */
        .info-section { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .section-title { font-size: 1.4rem; font-weight: 700; color: #222; margin-bottom: 15px; }

        .tags-container { display: flex; flex-wrap: wrap; gap: 10px; }
        
        .tag {
            background-color: #e3f2fd; /* Azul clarinho fundo */
            color: #1565c0; /* Azul escuro texto */
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 0.95rem;
            font-weight: 500;
            display: inline-block;
        }

        /* MAPA */
        .map-container { width: 100%; height: 350px; background: #eee; border-radius: 8px; overflow: hidden; margin-top: 10px; }
        iframe { width: 100%; height: 100%; border: 0; }

        /* GALERIA DE FOTOS */
        .gallery-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 20px; }
        @media(min-width: 768px) { .gallery-grid { grid-template-columns: repeat(5, 1fr); } }
        
        .gallery-item { width: 100%; height: 180px; object-fit: cover; border-radius: 4px; cursor: pointer; transition: 0.3s; }
        .gallery-item:hover { opacity: 0.8; }

        /* MODAL */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); justify-content: center; align-items: center; }
        .modal img { max-width: 90%; max-height: 90%; border-radius: 4px; }
        .close-modal { position: absolute; top: 20px; right: 30px; color: #fff; font-size: 40px; cursor: pointer; }

    </style>
</head>
<body>

<?php include 'topo.php'; ?>

<div class="container">
    <div class="breadcrumbs">
        <a href="index.php">Brasil</a> > 
        <?= htmlspecialchars($perfil['estado']) ?> > 
        <?= htmlspecialchars($perfil['cidade']) ?> > 
        <?= htmlspecialchars($perfil['nome']) ?>
    </div>

    <!-- CARD PRINCIPAL -->
    <div class="profile-card">
        <div class="profile-left">
            <img src="uploads/<?= htmlspecialchars($perfil['foto']) ?>" class="main-photo" onclick="abrirModal(this.src)">
        </div>
        <div class="profile-right">
            <h1 class="profile-name"><?= htmlspecialchars($perfil['nome']) ?>, <?= $perfil['idade'] ?></h1>
            <div class="profile-meta">
                <i class="fas fa-map-marker-alt" style="color:#c62828"></i> 
                <?= htmlspecialchars($perfil['bairro']) ?> - <?= htmlspecialchars($perfil['cidade']) ?>
            </div>
            
            <div style="font-size: 1.5rem; color: #2e7d32; font-weight: bold; margin-bottom: 20px;">
                Cachê: R$ <?= number_format($perfil['valor'], 2, ',', '.') ?>
            </div>

            <a href="https://wa.me/55<?= $whats_limpo ?>" target="_blank" class="btn-whatsapp">
                <i class="fab fa-whatsapp"></i> Falar no WhatsApp
            </a>

            <div class="descricao-texto">
                <?= nl2br(htmlspecialchars($perfil['descricao'])) ?>
            </div>
        </div>
    </div>

    <!-- SEÇÃO 1: SOBRE MIM (Tags) -->
    <div class="info-section">
        <div class="section-title">Sobre mim</div>
        <div class="tags-container">
            <div class="tag"><?= $perfil['idade'] ?> anos</div>
            <!-- Caracteristicas Físicas -->
            <?php foreach($carac_lista as $item): ?>
                <div class="tag"><?= trim($item) ?></div>
            <?php endforeach; ?>
            <!-- Formas de Pagamento -->
            <?php foreach($pagamentos_lista as $pg): ?>
                <div class="tag"><?= trim($pg) ?></div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- SEÇÃO 2: MEUS SERVIÇOS (Tags) -->
    <div class="info-section">
        <div class="section-title">Meus serviços</div>
        <div class="tags-container">
            <?php foreach($servicos_lista as $servico): ?>
                <div class="tag"><?= trim($servico) ?></div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- SEÇÃO 3: LUGAR DE ENCONTRO (Tags) -->
    <div class="info-section">
        <div class="section-title">Lugar de encontro</div>
        <div class="tags-container">
            <?php foreach($locais_lista as $local): ?>
                <div class="tag"><?= trim($local) ?></div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- SEÇÃO 4: FOTOS E VIDEOS -->
    <?php if(count($galeria) > 0): ?>
    <div class="info-section">
        <div class="section-title">Galeria de Fotos</div>
        <div class="gallery-grid">
            <?php foreach($galeria as $foto): ?>
                <img src="uploads/<?= $foto['arquivo'] ?>" class="gallery-item" onclick="abrirModal(this.src)">
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- SEÇÃO 5: MAPA -->
    <div class="info-section">
        <div class="section-title">Mapa</div>
        <div class="tags-container" style="margin-bottom:10px;">
            <div class="tag" style="background:#eee; color:#333;"><?= htmlspecialchars($perfil['bairro']) ?></div>
            <div class="tag" style="background:#eee; color:#333;"><?= htmlspecialchars($perfil['cidade']) ?></div>
        </div>
        <div class="map-container">
            <!-- Mapa do Google buscando pelo Bairro e Cidade -->
            <iframe 
                src="https://maps.google.com/maps?q=<?= $endereco_mapa ?>&t=&z=15&ie=UTF8&iwloc=&output=embed" 
                loading="lazy">
            </iframe>
        </div>
    </div>

</div>

<!-- MODAL -->
<div id="modalVisual" class="modal" onclick="this.style.display='none'">
    <span class="close-modal">&times;</span>
    <img id="imgModal" onclick="event.stopPropagation()">
</div>

<script>
    function abrirModal(src) {
        document.getElementById('imgModal').src = src;
        document.getElementById('modalVisual').style.display = 'flex';
    }
</script>

</body>
</html>
