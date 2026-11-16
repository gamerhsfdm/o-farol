# O FAROL — Jornal Escolar Sesc-Senac
## Sistema CMS Editorial Completo

Sistema completo de jornal escolar digital com portal público de credibilidade editorial e painel administrativo (CMS) desenvolvido sob medida em **PHP 8.2+**, **Tailwind CSS**, **JavaScript ES6+** e **MySQL/MariaDB**, pronto para o ambiente tradicional do **XAMPP**.

---

## 1. Instruções para Importação do Banco de Dados via phpMyAdmin

1. Abra o **XAMPP Control Panel** e inicie os serviços **Apache** e **MySQL**.
2. Abra seu navegador e acesse o phpMyAdmin: `http://localhost/phpmyadmin/`.
3. Clique em **Novo** (lado esquerdo) e crie um banco de dados com:
   - **Nome:** `o_farol`
   - **Agrupamento (Collation):** `utf8mb4_unicode_ci`
4. Com o banco `o_farol` selecionado, clique na aba **Importar** no menu superior.
5. Em **Arquivo para importar**, clique em **Escolher arquivo** e selecione o arquivo:
   `c:\xampp\htdocs\o-farol\database.sql`
6. Clique no botão **Executar** no final da página.
7. O banco será criado com todas as tabelas e a carga inicial idêntica à referência visual do jornal.

---

## 2. Acesso ao Painel Administrativo CMS

- **URL de Login:** `http://localhost/o-farol/admin/index.php`

---

## 3. Funcionalidades Implementadas

### 3.1. Frontend Editorial Fiel à Referência
- **Topo Institucional:** Edição Nº 18, data e selo de parceria institucional Sesc-Senac.
- **Cabeçalho Clássico:** Ilustração SVG do Farol, tipografia serifada de alto impacto e barra de navegação com categorias dinâmicas do banco de dados.
- **Manchete Principal (Hero):** Tag vermelha editorial, citação em destaque (*blockquote*), assinatura de múltiplos autores discentes, professor orientador e fotografia com legenda e créditos.
- **Ciência & Prática Técnica:** Card com foco em projetos integradores de robótica e eletrônica.
- **Jogos Escolares Interclasses:** Cobertura esportiva integrada a quadro dinâmico de resultados e chaveamentos.
- **Cultura & Empreendedorismo:** Divulgação em grade de eventos, oficinas e feiras.
- **Coluna de Opinião:** Artigo discente assinado com avatar e identificação de turma.
- **Flagrantes da Semana:** Galeria fotográfica escolar ("Campus em Foco").
- **Sidebar Dinâmica:**
  - *Clube do Livro (Obra do Mês):* Capa, resenha, próxima leitura e inscrições.
  - *Processos Seletivos:* Editais, bolsas e prazos com botão para download de calendário.
  - *Voz do Aluno (Enquete Interativa):* Votação assíncrona com prevenção de voto duplicado (hash de IP + UserAgent) e cálculo percentual dinâmico.
- **Faixa de Envio de Pautas:** Canal com formulário real para envio de matérias pelos estudantes.
- **Rodapé Institucional em 4 Colunas:** Corpo Editorial Discente, Orientação Pedagógica, Links Oficiais e Sede da Redação.

### 3.2. Painel Administrativo CMS (`/admin`)
- **Dashboard:** Indicadores reais em tempo real e gráfico com Chart.js.
- **Editor de Reportagens:** Editor de texto rico (Quill), atribuição de múltiplos autores, professor orientador, posições editoriais e agendamento.
- **Histórico & Restauração:** Criação de versões arquivadas a cada salvamento com restauração em 1 clique.
- **Layout da Homepage:** Seleção e alternância das notícias que ocupam cada seção da capa e gerenciamento do Clube do Livro.
- **Editorias & Categorias:** Criação, ordenação e controle de visibilidade no menu superior.
- **Equipe Editorial:** Cadastro de autores discentes com série, biografia e atribuição de matérias.
- **Biblioteca de Mídia:** Upload seguro de imagens com metadados (alt, legenda e créditos).
- **Caixa de Entrada de Pautas:** Avaliação e aprovação de propostas enviadas pelos alunos.
- **Configurações Gerais:** Edição dos dados institucionais, corpo docente e edição ativa.

---

## 4. Compilação do Tailwind CSS (Opcional)

Se desejar alterar classes e recompilar o arquivo CSS principal:
```bash
npm install
npm run build:css
```
O arquivo CSS pronto para produção já está compilado e disponível em `assets/css/main.css`.
