# Deploy e instruções de publicação

Este repositório é uma aplicação PHP simples que precisa de um servidor com PHP e MySQL para funcionar com todas as funcionalidades (login, upload de arquivos, API dinâmicas).

Opções de publicação

1) Deploy com Docker (recomendado para testes locais e servidores com Docker)

- Requisitos: Docker e Docker Compose.
- Como rodar localmente:

```bash
# copie .env.example para .env e ajuste se necessário
cp .env.example .env

# iniciar serviços
docker-compose up -d

# abrir no navegador em http://localhost:8080
# phpMyAdmin ficará em http://localhost:8081
```

- Observações:
  - A pasta `uploads/` é montada a partir do diretório do projeto. Garanta permissões de escrita (ex.: `chmod -R 775 uploads` e `chown -R $USER:www-data uploads`).
  - Ajuste as variáveis de ambiente no `docker-compose.yml` ou no arquivo `.env` para definir credenciais MySQL.

2) Deploy em um host PHP (cPanel / VPS / DigitalOcean / Render com PHP)

- Requisitos do servidor:
  - PHP 8.0+ com extensões: pdo_mysql, fileinfo, mbstring, gd (ou imagick)
  - MySQL/MariaDB
  - Apache ou Nginx (com PHP-FPM)
  - Espaço para uploads e permissão de escrita na pasta `uploads/`

- Passos básicos (cPanel / FTP):
  1. Envie todos os arquivos para a pasta pública (`public_html` ou equivalente).
  2. Crie um banco de dados MySQL e um usuário com senha.
  3. Atualize `config.php` para apontar para as credenciais do banco (ou use variáveis de ambiente se o host permitir).
  4. Garanta permissões na pasta `uploads/` (`chmod -R 775 uploads` e, se possível, `chown -R www-data:www-data uploads`).
  5. Configure HTTPS (Let's Encrypt ou certificado do host).

3) Deploy na Render (ou outro provider que aceite Docker)

- Render permite deploy via Dockerfile. Se preferir este método, use o `docker-compose.yml` para testes locais e crie um `Dockerfile` customizado para cada serviço.

Configurações recomendadas do PHP

- `upload_max_filesize`: 50M
- `post_max_size`: 60M
- `memory_limit`: 256M
- `max_execution_time`: 120

Segurança e recomendações

- Não exponha pastas com documentos sensíveis. A pasta `uploads/` contém documentos sigilosos e fotos; armazene-os fora do diretório público ou controle acesso via autenticação quando possível.
- Use HTTPS obrigatório.
- Faça backups regulares do banco de dados e da pasta `uploads/`.

Scripts úteis

- Criar banco e usuário (MySQL):

```sql
CREATE DATABASE classificados_adultos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ifoodee'@'%' IDENTIFIED BY 'SENHA_FORTE';
GRANT ALL PRIVILEGES ON classificados_adultos.* TO 'ifoodee'@'%';
FLUSH PRIVILEGES;
```

Precisa que eu crie um `Dockerfile` ou automatize o deploy em um serviço específico (DigitalOcean, Render, Hetzner)?