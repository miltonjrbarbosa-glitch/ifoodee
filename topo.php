<?php 
// Inicia a sessão para verificar se o anunciante está logado
if (session_status() === PHP_SESSION_NONE) session_start(); 
?>

<style>
    /* RESET BÁSICO */
    .top-bar-container { font-family: 'Roboto', sans-serif; }
    
    /* --- BARRA SUPERIOR (LOGO E BUSCA) --- */
    .top-bar {
        background: #fff; border-bottom: 1px solid #ddd; padding: 15px 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        display: flex; flex-direction: row; align-items: center; justify-content: center; gap: 40px; 
    }

    .logo-link { display: block; flex-shrink: 0; text-decoration: none; }
    .logo-img { width: 600px; height: 150px; object-fit: contain; display: block; }
    
    .search-form { display: flex; gap: 10px; width: 100%; max-width: 900px; align-items: center; }
    
    .input-group { border: 1px solid #ccc; border-radius: 4px; display: flex; align-items: center; background: #fff; height: 42px; width: 100%; }
    
    .input-local { flex: 2; }
    .input-local input { border: none; outline: none; padding: 0 15px; width: 100%; font-size: 0.95rem; color: #333; }
    
    /* SELECT DO RAIO (KM) */
    .select-km { border-left: 1px solid #ccc; background: #f9f9f9; height: 100%; display: flex; align-items: center; }
    .select-km select { border: none; background: transparent; padding: 0 10px; outline: none; cursor: pointer; color: #555; font-size: 0.85rem;}
    
    .input-busca { flex: 3; }
    .input-busca input { border: none; outline: none; padding: 0 15px; width: 100%; font-size: 0.95rem; }

    .btn-procurar { background-color: #c62828; color: white; border: none; border-radius: 4px; padding: 0 25px; height: 42px; font-weight: bold; font-size: 1rem; cursor: pointer; display: flex; align-items: center; gap: 8px; flex-shrink: 0; transition: 0.3s; }
    .btn-procurar:hover { background-color: #b71c1c; }
    
    .btn-mic { background: #c62828; color: white; border: none; border-radius: 4px; width: 42px; height: 42px; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }

    /* --- SUB MENU --- */
    .sub-menu { background: #fff; border-bottom: 1px solid #e0e0e0; padding: 10px 0; margin-bottom: 20px; position: relative; z-index: 100; }
    .sub-menu-content { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem; color: #333; font-weight: 500; }
    
    .sub-links { display: flex; align-items: center; }
    .sub-links div, .sub-links a { display: inline-flex; align-items: center; gap: 5px; margin-right: 20px; cursor: pointer; text-decoration: none; color: #333; }
    .sub-links a:hover, .sub-links div:hover { color: #c62828; }
    .fa-crosshairs { color: #00897b; }

    /* --- DROPDOWN (MENU SUSPENSO CIDADES) --- */
    .dropdown { position: relative; display: inline-block; }
    .dropdown-content {
        display: none;
        position: absolute;
        background-color: #fff;
        min-width: 200px;
        box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
        z-index: 1000;
        border-radius: 4px;
        top: 25px;
        left: 0;
        max-height: 400px;
        overflow-y: auto;
    }
    
    .dropdown:hover .dropdown-content { display: block; }
    
    .dropdown-content a {
        color: #333; padding: 12px 16px; text-decoration: none; display: block; font-size: 0.9rem; border-bottom: 1px solid #f1f1f1; margin-right: 0;
    }
    .dropdown-content a:hover { background-color: #f9f9f9; color: #c62828; }

    /* BOTÕES LOGADO/DESLOGADO */
    .btn-login-topo { color: #555; font-weight: normal; }
    .btn-destaque { color: #c62828 !important; font-weight: bold; }
    .btn-painel-topo { background: #3f51b5; color: white !important; padding: 5px 15px; border-radius: 15px; }

    /* RESPONSIVO MOBILE */
    @media (max-width: 900px) {
        .top-bar { flex-direction: column; gap: 15px; padding: 15px; }
        .search-form { flex-direction: column; width: 100%; }
        .input-group, .btn-procurar, .btn-mic { width: 100%; }
        .logo-img { width: 100%; max-width: 200px; height: auto; margin-bottom: 5px; } 
        .sub-menu-content { flex-direction: column; gap: 10px; }
        .dropdown:hover .dropdown-content { position: relative; top: 0; box-shadow: none; border: 1px solid #eee; width: 100%; }
    }
</style>

<div class="top-bar-container">
    <div class="top-bar">
        <a href="index.php" class="logo-link">
            <img src="img/logo.png" alt="Logo" class="logo-img" onerror="this.style.display='none'; document.getElementById('txt-logo-topo').style.display='block'">
            <h2 id="txt-logo-topo" style="display:none; color:#c62828; margin:0;">SEU LOGO</h2>
        </a>

        <!-- FORMULÁRIO DE BUSCA -->
        <form class="search-form" action="index.php" method="GET">
            <div class="input-group input-local">
                <input type="text" name="local" id="topo-local" placeholder="Perto de mim" value="<?= htmlspecialchars($_GET['local'] ?? '') ?>">
                
                <!-- SELECT DE KM ATUALIZADO -->
                <div class="select-km">
                    <select name="raio" onchange="this.form.submit()">
                        <?php 
                        // Pega o valor atual ou 3 como padrão
                        $raio_sel = isset($_GET['raio']) ? (int)$_GET['raio'] : 3; 
                        
                        // Lista exata que você pediu
                        $distancias = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 30];

                        foreach ($distancias as $km) {
                            $selected = ($raio_sel == $km) ? 'selected' : '';
                            echo "<option value='{$km}' {$selected}>+ {$km} km</option>";
                        }
                        ?>
                    </select>
                    <i class="fas fa-chevron-down" style="padding-right: 5px;"></i>
                </div>
            </div>

            <div class="input-group input-busca">
                <input type="text" name="q" placeholder="O que procura?" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            </div>

            <button type="submit" class="btn-procurar"><i class="fas fa-search"></i> Procurar</button>
            <button type="button" class="btn-mic"><i class="fas fa-microphone"></i></button>
        </form>
    </div>

    <div class="sub-menu">
        <div class="sub-menu-content">
            <div class="sub-links">
                
                <!-- DROPDOWN CIDADES MG -->
                <div class="dropdown">
                    <div style="font-weight:bold;">
                        <i class="fas fa-map-marker-alt"></i> Cidades de MG <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-content">
                        <a href="index.php?local=Belo Horizonte">Belo Horizonte</a>
                        <a href="index.php?local=Contagem">Contagem</a>
                        <a href="index.php?local=Uberlândia">Uberlândia</a>
                        <a href="index.php?local=Juiz de Fora">Juiz de Fora</a>
                        <a href="index.php?local=Betim">Betim</a>
                        <a href="index.php?local=Montes Claros">Montes Claros</a>
                        <a href="index.php?local=Uberaba">Uberaba</a>
                        <a href="index.php?local=Governador Valadares">Governador Valadares</a>
                        <a href="index.php?local=Ipatinga">Ipatinga</a>
                        <a href="index.php?local=Sete Lagoas">Sete Lagoas</a>
                        <a href="index.php?local=Divinópolis">Divinópolis</a>
                        <a href="index.php?local=Santa Luzia">Santa Luzia</a>
                        <a href="index.php?local=Ibirité">Ibirité</a>
                        <a href="index.php?local=Poços de Caldas">Poços de Caldas</a>
                        <a href="index.php?local=Pouso Alegre">Pouso Alegre</a>
                    </div>
                </div>

                <!-- BOTÃO GPS -->
                <div onclick="usarLocalizacaoTopo()" style="margin-left: 20px;">
                    Localizado <i class="fas fa-crosshairs"></i>
                </div>
            </div>
            
            <div class="sub-links">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <!-- LOGADO -->
                    <span style="color:#777; margin-right:10px;">Olá, <?= explode(' ', $_SESSION['user_nome'])[0] ?></span>
                    <a href="painel.php" class="btn-painel-topo"><i class="fas fa-user-edit"></i> Meu Painel</a>
                    <a href="logout.php" style="color:red; font-size:0.85rem;">Sair</a>
                <?php else: ?>
                    <!-- DESLOGADO -->
                    <a href="login.php" class="btn-login-topo"><i class="fas fa-user"></i> Login</a>
                    <a href="anunciar.php" class="btn-destaque">ANUNCIAR GRÁTIS</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPT DE LOCALIZAÇÃO -->
<script>
function usarLocalizacaoTopo() {
    // Altera o texto do input do topo para dar feedback
    const inputLocal = document.getElementById('topo-local');
    if(inputLocal) inputLocal.placeholder = "Buscando GPS...";

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            
            // Pega o raio selecionado no momento
            const selectRaio = document.querySelector('select[name="raio"]');
            const raioValor = selectRaio ? selectRaio.value : 30;

            // Redireciona a página passando a Lat, Lon e o Raio
            window.location.href = "index.php?lat=" + lat + "&lon=" + lon + "&raio=" + raioValor;
        }, function(error) {
            alert("Não foi possível pegar sua localização. Verifique se o GPS está ativo.");
            if(inputLocal) inputLocal.placeholder = "Perto de mim";
        });
    } else {
        alert("Seu navegador não suporta geolocalização.");
    }
}
</script>
