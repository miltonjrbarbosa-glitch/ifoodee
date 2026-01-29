<?php
// index.php

// 1. Verifica conexão com banco
if (file_exists('config.php')) {
    require 'config.php';
} else {
    die("Erro: O arquivo config.php não foi encontrado.");
}

// --- CONFIGURAÇÃO DA PESQUISA ---
$where = "WHERE 1=1";
$params = [];
$campo_distancia = ""; 
$order_by = "ORDER BY id DESC"; // Ordenação padrão (os mais novos primeiro)
$having = ""; // Filtro de raio (km)

// Pega o raio selecionado ou define 30km como padrão
$raio = isset($_GET['raio']) ? (int)$_GET['raio'] : 30;

// ============================================================
// LÓGICA DE FILTRAGEM
// ============================================================

// CENÁRIO 1: USUÁRIO ENVIOU O GPS (LATITUDE E LONGITUDE)
if (!empty($_GET['lat']) && !empty($_GET['lon'])) {
    $user_lat = $_GET['lat'];
    $user_lon = $_GET['lon'];

    // Fórmula de Haversine (Calcula distância em KM na hora)
    $campo_distancia = ", (6371 * acos(cos(radians(:user_lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians(:user_lon)) + sin(radians(:user_lat)) * sin(radians(latitude)))) AS distancia";
    
    $params[':user_lat'] = $user_lat;
    $params[':user_lon'] = $user_lon;

    // Filtra pelo raio escolhido (0, 1, 2... 30)
    // Se o raio for 0, usamos uma margem mínima de 0.5km para exibir quem está muito perto
    if ($raio == 0) {
         $having = "HAVING distancia <= 0.5"; 
    } else {
         $having = "HAVING distancia <= " . $raio;
    }

    $order_by = "ORDER BY distancia ASC"; // Ordena do mais perto para o mais longe
    $termo_local = "Perto de mim (+{$raio}km)";
} 

// CENÁRIO 2: USUÁRIO DIGITOU UMA CIDADE/BAIRRO NA BUSCA
elseif (!empty($_GET['local'])) {
    $where .= " AND (cidade LIKE :local OR bairro LIKE :local OR estado LIKE :local)";
    $params[':local'] = '%' . $_GET['local'] . '%';
    $termo_local = htmlspecialchars($_GET['local']);
} 

// CENÁRIO 3 (PADRÃO): NENHUM FILTRO -> MOSTRA BELO HORIZONTE
else {
    $where .= " AND cidade LIKE :padrao";
    $params[':padrao'] = '%Belo Horizonte%'; 
    $termo_local = "Belo Horizonte";
}

// FILTRO ADICIONAL: TEXTO (Nome ou Descrição)
if (!empty($_GET['q'])) {
    $where .= " AND (nome LIKE :q OR descricao LIKE :q)";
    $params[':q'] = '%' . $_GET['q'] . '%';
}

// EXECUÇÃO DA CONSULTA
try {
    $sql = "SELECT * $campo_distancia FROM perfis $where $having $order_by";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $perfis = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $perfis = [];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acompanhantes - <?= $termo_local ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        * { box-sizing: border-box; }
        body { background-color: #f2f2f2; font-family: 'Roboto', sans-serif; margin: 0; padding: 0; }
        
        .main-content { max-width: 1200px; margin: 20px auto; padding: 0 20px; padding-bottom: 50px; }
        
        /* Breadcrumbs e Títulos */
        .breadcrumbs { font-size: 0.85rem; color: #777; margin-bottom: 10px; }
        .location-text { color: #777; font-size: 0.9rem; margin-top: 5px; margin-bottom: 20px;}

        .page-title-box { margin-bottom: 5px; }
        .page-title { 
            background: #3f51b5; 
            color: white; 
            padding: 5px 15px; 
            display: inline-block; 
            font-weight: bold; 
            font-size: 1.2rem; 
        }

        /* GRID DE CARDS */
        .grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        @media (min-width: 768px) { .grid { grid-template-columns: repeat(5, 1fr); gap: 15px; } }

        /* ESTILO DO CARD */
        .card { 
            background: #fff; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
            overflow: hidden; 
            position: relative; 
            transition: box-shadow 0.2s; 
            cursor: pointer; 
            text-decoration: none; 
            display: block; 
        }
        .card:hover { 
            box-shadow: 0 8px 20px rgba(0,0,0,0.15); 
            transform: translateY(-3px); 
            transition: 0.3s; 
        }
        
        .card img { 
            width: 100%; 
            height: 320px; 
            object-fit: cover; 
            display: block; 
        }

        /* ETIQUETAS */
        .tag-online { 
            position: absolute; bottom: 65px; left: 0; 
            background: #ffd700; color: #000; 
            padding: 3px 10px; font-size: 0.75rem; 
            font-weight: bold; text-transform: uppercase; 
        }
        
        .tag-valor {
            position: absolute; top: 10px; right: 10px;
            background: rgba(0,0,0,0.7); color: #fff;
            padding: 4px 8px; border-radius: 4px;
            font-size: 0.85rem; font-weight: bold;
        }

        /* ETIQUETA AZUL DE DISTÂNCIA (SÓ APARECE COM GPS) */
        .tag-distancia {
            position: absolute; top: 10px; left: 10px;
            background: rgba(33, 150, 243, 0.95); color: white;
            padding: 4px 8px; border-radius: 4px;
            font-size: 0.8rem; font-weight: bold;
            display: flex; align-items: center; gap: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .card-info { padding: 10px; }
        .card-name { font-weight: bold; color: #333; font-size: 1.1rem; text-transform: capitalize; margin-bottom: 3px; }
        .card-local { color: #666; font-size: 0.85rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .card-local i { color: #c62828; margin-right: 4px; }

        @media (max-width: 900px) {
            .grid { gap: 10px; }
            .card img { height: 250px; }
        }
    </style>
</head>
<body>

    <!-- Inclui o Topo (Busca, Logo, Menu) -->
    <?php include 'topo.php'; ?>

    <div class="main-content">
        <!-- Navegação / Breadcrumbs -->
        <div class="breadcrumbs">Brasil > <?= $termo_local ?></div>
        
        <div class="page-title-box">
            <div class="page-title">
                <?php if(isset($_GET['lat'])): ?>
                    Acompanhantes Perto de Mim (GPS)
                <?php else: ?>
                    Acompanhantes <?= strtolower($termo_local) ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="location-text">Encontre acompanhantes em Brasil > <?= $termo_local ?></div>

        <!-- LISTA DE ANÚNCIOS -->
        <div class="grid">
            <?php if(!empty($perfis) && count($perfis) > 0): ?>
                <?php foreach ($perfis as $perfil): ?>
                <a href="perfil.php?id=<?= $perfil['id'] ?>" class="card">
                    <div style="position:relative;">
                        <!-- Foto Principal -->
                        <img src="uploads/<?= htmlspecialchars($perfil['foto']) ?>" alt="Foto" onerror="this.src='https://via.placeholder.com/300x400?text=Sem+Foto'">
                        
                        <!-- Etiqueta de Distância (Só aparece se tiver GPS calculado) -->
                        <?php if(isset($perfil['distancia'])): ?>
                            <div class="tag-distancia">
                                <i class="fas fa-location-arrow"></i> 
                                <?= number_format($perfil['distancia'], 1, ',', '.') ?> km
                            </div>
                        <?php endif; ?>

                        <!-- Etiqueta de Valor -->
                        <?php if(!empty($perfil['valor'])): ?>
                            <div class="tag-valor">R$ <?= number_format($perfil['valor'], 0, ',', '.') ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tag-online">Online agora</div>
                    
                    <div class="card-info">
                        <div class="card-name">
                            <?= htmlspecialchars($perfil['nome']) ?>, <?= $perfil['idade'] ?>
                        </div>
                        <div class="card-local">
                            <i class="fas fa-map-marker-alt"></i>
                            <?= htmlspecialchars($perfil['cidade']) ?> - <?= htmlspecialchars($perfil['bairro'] ?? 'Centro') ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Mensagem se não achar nada -->
                <div style="grid-column: 1 / -1; padding: 40px; color: #666; text-align: center; background: #fff; border: 1px solid #ddd; border-radius: 4px;">
                    <i class="fas fa-map-marker-alt" style="font-size: 2rem; margin-bottom:10px; display:block; color:#ccc;"></i>
                    <p>Nenhum perfil encontrado nesta região.</p>
                    <a href="index.php?local=Belo Horizonte" style="color:#c62828; font-weight:bold;">Ver perfis em Belo Horizonte</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Script de correção para o botão GPS caso necessário -->
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Garante que o botão de GPS do submenu use a função do topo
        const btnLocal = document.querySelector('.sub-links div[onclick*="usarLocalizacao"]');
        if(btnLocal && typeof usarLocalizacaoTopo === 'function') {
            btnLocal.onclick = usarLocalizacaoTopo;
        }
    });
    </script>

</body>
</html>