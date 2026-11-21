<?php
/**
 * O FAROL — Página 404 (Página Não Encontrada)
 * Experiência imersiva, náutica e cinematográfica fiel ao conceito visual "O Farol"
 */
declare(strict_types=1);
http_response_code(404);

require_once __DIR__ . '/includes/functions.php';
$settings = get_site_settings();
$edicao_numero = $settings['edicao_atual_numero'] ?? '18';
$edicao_data = $settings['edicao_atual_data'] ?? 'Junho de 2026';
$site_nome = $settings['site_nome'] ?? 'O FAROL';
$site_subtitulo = $settings['site_subtitulo'] ?? 'Jornal Escolar Sesc-Senac';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>404 — Página Não Encontrada | <?= e($site_nome) ?></title>
  <meta name="description" content="Parece que esta notícia se perdeu no horizonte. Nosso farol continua aceso para guiar você de volta.">
  <meta name="theme-color" content="#03161c">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;0,6..72,600;1,6..72,400&display=swap" rel="stylesheet">

  <style>
    /* ========================================================
       RESET & TOKENS DE COR FIÉIS À REFERÊNCIA
       ======================================================== */
    *, *::before, *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    :root {
      --bg-ocean-deep: #03141a;
      --bg-ocean-mid: #052028;
      --bg-ocean-card: #082832;
      --crimson-brand: #a51f2a;
      --crimson-glow: #e11d48;
      --gold-light: #fcd34d;
      --gold-beam: rgba(253, 224, 71, 0.28);
      --text-main: #f1f5f9;
      --text-muted: #829ba6;
      --line-subtle: rgba(255, 255, 255, 0.08);
      --font-headline: 'Newsreader', Georgia, serif;
      --font-ui: 'DM Sans', system-ui, -apple-system, sans-serif;
    }

    html, body {
      height: 100%;
      width: 100%;
      background-color: var(--bg-ocean-deep);
      color: var(--text-main);
      font-family: var(--font-ui);
      overflow-x: hidden;
      position: relative;
    }

    /* Linha vermelha no topo extremo da página */
    .top-edge-red-line {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, #881337 0%, #be123c 25%, #e11d48 50%, #be123c 75%, #881337 100%);
      z-index: 1000;
      box-shadow: 0 0 12px rgba(225, 29, 72, 0.6);
    }

    /* Grid Náutica e Céu Estrelado */
    .nautical-scene {
      position: relative;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      background: radial-gradient(circle at 78% 42%, #083340 0%, #041d25 45%, #021116 100%);
      overflow: hidden;
    }

    /* Malha milimetrada sutil náutica */
    .nautical-grid {
      position: absolute;
      inset: 0;
      background-image: 
        linear-gradient(to right, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
      background-size: 50px 50px;
      pointer-events: none;
      z-index: 1;
    }

    /* Estrelas cintilantes */
    .stars-layer {
      position: absolute;
      inset: 0;
      pointer-events: none;
      z-index: 2;
    }

    .star {
      position: absolute;
      background: #fff;
      border-radius: 50%;
      opacity: 0.6;
      animation: starTwinkle 4s infinite ease-in-out;
    }

    @keyframes starTwinkle {
      0%, 100% { opacity: 0.2; transform: scale(0.8); }
      50% { opacity: 0.9; transform: scale(1.2); box-shadow: 0 0 6px rgba(255, 255, 255, 0.9); }
    }

    /* ========================================================
       CABEÇALHO MINIMALISTA
       ======================================================== */
    .scene-header {
      position: relative;
      z-index: 20;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1.5rem 2.5rem 1.25rem 2.5rem;
      border-bottom: 1px solid var(--line-subtle);
    }

    .brand-group {
      display: flex;
      align-items: center;
      gap: 0.85rem;
      text-decoration: none;
      color: inherit;
    }

    .brand-icon-circle {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid rgba(255, 255, 255, 0.15);
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }

    .brand-text-block {
      display: flex;
      flex-direction: column;
    }

    .brand-title {
      font-family: var(--font-headline);
      font-size: 1.35rem;
      font-weight: 700;
      letter-spacing: -0.02em;
      color: #fff;
      line-height: 1.1;
    }

    .brand-sub {
      font-size: 0.75rem;
      color: var(--text-muted);
      letter-spacing: 0.01em;
    }

    /* Status central "SINAL INTERROMPIDO" */
    .status-beacon {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: #cbd5e1;
      padding: 0.35rem 0.85rem;
      background: rgba(0, 0, 0, 0.25);
      border: 1px solid rgba(255, 255, 255, 0.07);
      border-radius: 9999px;
    }

    .status-dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: var(--crimson-brand);
      animation: pulseRed 2s infinite ease-in-out;
    }

    @keyframes pulseRed {
      0%, 100% { opacity: 0.4; transform: scale(0.9); box-shadow: 0 0 0 rgba(225, 29, 72, 0); }
      50% { opacity: 1; transform: scale(1.15); box-shadow: 0 0 10px rgba(225, 29, 72, 0.8); }
    }

    .header-edition {
      font-size: 0.78rem;
      color: var(--text-muted);
      letter-spacing: 0.02em;
      font-weight: 400;
    }

    /* ========================================================
       404 GIGANTE AO FUNDO
       ======================================================== */
    .giant-404-bg {
      position: absolute;
      top: 50%;
      left: 54%;
      transform: translate(-50%, -52%);
      font-family: var(--font-headline);
      font-size: clamp(14rem, 28vw, 30rem);
      font-weight: 400;
      line-height: 0.8;
      letter-spacing: -0.04em;
      color: transparent;
      -webkit-text-stroke: 1.5px rgba(255, 255, 255, 0.07);
      text-shadow: 0 0 60px rgba(0, 0, 0, 0.4);
      user-select: none;
      pointer-events: none;
      z-index: 3;
      white-space: nowrap;
      opacity: 0.75;
      transition: transform 0.2s cubic-bezier(0.1, 0, 0.2, 1);
    }

    /* ========================================================
       CONTEÚDO PRINCIPAL (LAYOUT EM DUAS ÁREAS INTEGRADAS)
       ======================================================== */
    .scene-body {
      position: relative;
      z-index: 10;
      flex: 1;
      display: flex;
      align-items: center;
      padding: 2rem 3rem 0 3.5rem;
      max-width: 1440px;
      width: 100%;
      margin: 0 auto;
    }

    .scene-grid-inner {
      width: 100%;
      display: grid;
      grid-template-columns: minmax(360px, 500px) 1fr;
      align-items: center;
      gap: 3rem;
    }

    /* Coluna de Texto à Esquerda com Linha Delimitadora */
    .editorial-col {
      position: relative;
      padding-left: 1.75rem;
      border-left: 2px solid rgba(225, 29, 72, 0.85);
      animation: fadeInLeft 0.8s ease-out;
    }

    @keyframes fadeInLeft {
      from { opacity: 0; transform: translateX(-24px); }
      to { opacity: 1; transform: translateX(0); }
    }

    .badge-error-tag {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      margin-bottom: 0.85rem;
    }

    .badge-404-pill {
      background: var(--crimson-brand);
      color: #fff;
      font-size: 0.72rem;
      font-weight: 700;
      padding: 0.2rem 0.55rem;
      border-radius: 4px;
      letter-spacing: 0.04em;
    }

    .badge-label-tech {
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: #e2e8f0;
    }

    .editorial-timestamp {
      font-size: 0.75rem;
      color: var(--text-muted);
      margin-bottom: 1.25rem;
      letter-spacing: 0.02em;
    }

    .editorial-headline {
      font-family: var(--font-headline);
      font-size: clamp(2.4rem, 4.2vw, 3.8rem);
      font-weight: 500;
      line-height: 1.08;
      color: #ffffff;
      letter-spacing: -0.02em;
      margin-bottom: 1.5rem;
      text-wrap: balance;
    }

    .editorial-description {
      font-size: clamp(0.88rem, 1.05vw, 1rem);
      line-height: 1.6;
      color: #94a3b8;
      margin-bottom: 2.2rem;
      max-width: 440px;
    }

    /* Botão Voltar para a Capa */
    .btn-return-home {
      display: inline-flex;
      align-items: center;
      gap: 1.25rem;
      background: transparent;
      border: 1px solid rgba(255, 255, 255, 0.28);
      border-radius: 9999px;
      padding: 0.45rem 0.5rem 0.45rem 1.4rem;
      color: #ffffff;
      font-size: 0.88rem;
      font-weight: 500;
      text-decoration: none;
      transition: all 0.3s ease;
      cursor: pointer;
    }

    .btn-return-home:hover {
      border-color: #ffffff;
      background: rgba(255, 255, 255, 0.06);
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
    }

    .btn-arrow-circle {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background: var(--crimson-brand);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      transition: transform 0.3s ease, background 0.3s ease;
    }

    .btn-return-home:hover .btn-arrow-circle {
      transform: translateX(4px);
      background: #be123c;
    }

    /* ========================================================
       ILUSTRAÇÃO DO FAROL & CENÁRIO NÁUTICO À DIREITA
       ======================================================== */
    .lighthouse-col {
      position: relative;
      display: flex;
      justify-content: flex-end;
      align-items: flex-end;
      min-height: 480px;
      padding-right: 2rem;
    }

    .lighthouse-stage {
      position: relative;
      width: 320px;
      height: 480px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-end;
    }

    /* A Lua / Brilho suave */
    .moon-halo {
      position: absolute;
      top: 52px;
      right: 32px;
      width: 46px;
      height: 46px;
      border-radius: 50%;
      background: radial-gradient(circle, #fef08a 0%, #fde047 50%, #eab308 100%);
      box-shadow: 0 0 35px 12px rgba(253, 224, 71, 0.4);
      z-index: 5;
    }

    /* Feixe de Luz Giratório do Farol */
    .lightbeam-wrap {
      position: absolute;
      top: 78px;
      left: 160px;
      width: 0;
      height: 0;
      z-index: 6;
      pointer-events: none;
      transform-origin: 0 0;
      animation: rotateLightBeam 14s infinite ease-in-out;
    }

    @keyframes rotateLightBeam {
      0% {
        transform: rotate(-15deg);
        opacity: 0.85;
      }
      25% {
        transform: rotate(-35deg);
        opacity: 0.95;
      }
      50% {
        transform: rotate(-5deg);
        opacity: 0.7;
      }
      75% {
        transform: rotate(-45deg);
        opacity: 0.95;
      }
      100% {
        transform: rotate(-15deg);
        opacity: 0.85;
      }
    }

    .lightbeam-cone {
      position: absolute;
      top: -60px;
      left: -900px;
      width: 900px;
      height: 120px;
      background: linear-gradient(to left, rgba(254, 240, 138, 0.45) 0%, rgba(253, 224, 71, 0.22) 30%, rgba(253, 224, 71, 0.05) 75%, transparent 100%);
      clip-path: polygon(100% 45%, 100% 55%, 0% 100%, 0% 0%);
      filter: blur(5px);
    }

    /* Farol em SVG estruturado geometricamente */
    .lighthouse-svg-wrap {
      position: relative;
      z-index: 8;
      width: 220px;
      height: 380px;
      filter: drop-shadow(0 15px 30px rgba(0, 0, 0, 0.6));
      animation: lighthouseFloat 6s infinite ease-in-out;
    }

    @keyframes lighthouseFloat {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-4px); }
    }

    /* Brilho pulsante no lanternim */
    .lantern-glow {
      animation: lanternPulse 3s infinite alternate ease-in-out;
    }

    @keyframes lanternPulse {
      0% { opacity: 0.85; filter: drop-shadow(0 0 10px #fde047); }
      100% { opacity: 1; filter: drop-shadow(0 0 25px #fef08a); }
    }

    /* ========================================================
       OCEANO & CAMADAS DE ONDAS ANIMADAS NA PARTE INFERIOR
       ======================================================== */
    .ocean-container {
      position: relative;
      width: 100%;
      height: 140px;
      overflow: hidden;
      z-index: 9;
      margin-top: -40px;
    }

    .wave {
      position: absolute;
      bottom: 0;
      left: 0;
      width: 200%;
      height: 100%;
      background-repeat: repeat-x;
      transform: translate3d(0, 0, 0);
    }

    .wave--back {
      opacity: 0.45;
      animation: waveMove 18s linear infinite;
      z-index: 1;
    }

    .wave--mid {
      opacity: 0.75;
      animation: waveMove 12s linear infinite reverse;
      z-index: 2;
    }

    .wave--front {
      opacity: 1;
      animation: waveMove 8s linear infinite;
      z-index: 3;
    }

    @keyframes waveMove {
      0% { transform: translateX(0); }
      100% { transform: translateX(-50%); }
    }

    /* ========================================================
       RODAPÉ MINIMALISTA COM BÚSSOLA E COORDENADAS
       ======================================================== */
    .scene-footer {
      position: relative;
      z-index: 20;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1.25rem 2.5rem 1.5rem 2.5rem;
      border-top: 1px solid var(--line-subtle);
      background: rgba(3, 20, 26, 0.85);
      backdrop-filter: blur(8px);
      font-size: 0.72rem;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--text-muted);
    }

    .footer-left-guide {
      display: flex;
      align-items: center;
      gap: 0.85rem;
    }

    .guide-line {
      width: 32px;
      height: 1px;
      background: rgba(225, 29, 72, 0.6);
    }

    .guide-text {
      color: #94a3b8;
      font-weight: 600;
    }

    .footer-right-coords {
      display: flex;
      align-items: center;
      gap: 0.85rem;
    }

    .compass-icon-wrap {
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--text-muted);
      animation: compassRotate 20s infinite linear;
    }

    @keyframes compassRotate {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    .coords-text {
      display: flex;
      flex-direction: column;
      text-align: right;
      line-height: 1.25;
    }

    .coords-title {
      font-size: 0.65rem;
      color: #64748b;
      font-weight: 700;
    }

    .coords-val {
      color: #94a3b8;
      font-variant-numeric: tabular-nums;
    }

    /* Botão de Ajuda flutuante estilo Interrogação no canto */
    .floating-help-btn {
      position: fixed;
      bottom: 1.25rem;
      right: 1.25rem;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: rgba(15, 23, 42, 0.75);
      border: 1px solid rgba(255, 255, 255, 0.15);
      color: #94a3b8;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.8rem;
      font-weight: 700;
      text-decoration: none;
      z-index: 100;
      transition: all 0.2s ease;
    }

    .floating-help-btn:hover {
      background: rgba(225, 29, 72, 0.9);
      color: #fff;
      border-color: transparent;
      transform: scale(1.1);
    }

    /* ========================================================
       RESPONSIVIDADE PRECISA (TABLET E CELULAR)
       ======================================================== */
    @media (max-width: 1024px) {
      .scene-header { padding: 1.25rem 1.5rem; }
      .scene-body { padding: 2rem 1.5rem 0 1.5rem; }
      .scene-grid-inner {
        grid-template-columns: 1fr;
        gap: 2.5rem;
      }
      .giant-404-bg {
        left: 50%;
        top: 38%;
        font-size: 16rem;
        opacity: 0.4;
      }
      .lighthouse-col {
        justify-content: center;
        min-height: 340px;
        padding-right: 0;
      }
      .lighthouse-stage {
        height: 340px;
      }
      .lighthouse-svg-wrap {
        height: 280px;
        width: 170px;
      }
      .lightbeam-cone {
        left: -600px;
        width: 600px;
      }
    }

    @media (max-width: 640px) {
      .status-beacon { display: none; }
      .scene-header { padding: 1rem; }
      .brand-title { font-size: 1.15rem; }
      .editorial-col { padding-left: 1.25rem; }
      .editorial-headline { font-size: 2.2rem; }
      .scene-footer {
        flex-direction: column;
        gap: 0.75rem;
        text-align: center;
        padding: 1rem;
      }
      .coords-text { text-align: center; }
      .giant-404-bg { font-size: 11rem; top: 40%; }
      .floating-help-btn { display: none; }
    }

    /* Redução de Movimento Acessível */
    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
      }
      .giant-404-bg { transform: translate(-50%, -52%) !important; }
    }
  </style>
</head>
<body>

  <!-- Linha vermelha no topo da página -->
  <div class="top-edge-red-line" aria-hidden="true"></div>

  <div class="nautical-scene" id="scene-container">
    
    <!-- Malha náutica milimetrada -->
    <div class="nautical-grid" aria-hidden="true"></div>

    <!-- Estrelas no céu noturno -->
    <div class="stars-layer" aria-hidden="true">
      <div class="star" style="top:18%;left:24%;width:2.5px;height:2.5px;animation-delay:0.3s"></div>
      <div class="star" style="top:26%;left:48%;width:3px;height:3px;animation-delay:1.5s"></div>
      <div class="star" style="top:14%;left:72%;width:2px;height:2px;animation-delay:2.1s"></div>
      <div class="star" style="top:38%;left:86%;width:3px;height:3px;animation-delay:0.8s"></div>
      <div class="star" style="top:44%;left:15%;width:2px;height:2px;animation-delay:2.8s"></div>
      <div class="star" style="top:10%;left:92%;width:2.5px;height:2.5px;animation-delay:1.2s"></div>
    </div>

    <!-- Grande 404 translúcido integrado artisticamente ao fundo -->
    <div class="giant-404-bg" id="giant-404" aria-hidden="true">404</div>

    <!-- 1. CABEÇALHO -->
    <header class="scene-header">
      <a href="<?= BASE_URL ?>/index.php" class="brand-group" title="Voltar à Capa de O FAROL">
        <div class="brand-icon-circle">
          <!-- Ícone do Farol Institucional -->
          <svg width="22" height="22" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M26 50L29 23H35L38 50H26Z" fill="#0f5368" stroke="#ffffff" stroke-width="1.5"/>
            <path d="M27.5 36.5H36.5" stroke="#ffffff" stroke-width="2"/>
            <path d="M28.5 28.5H35.5" stroke="#ffffff" stroke-width="2"/>
            <rect x="29" y="17" width="6" height="6" fill="#f59e0b"/>
            <path d="M30 13L32 11L34 13V17H30V13Z" fill="#be123c"/>
          </svg>
        </div>
        <div class="brand-text-block">
          <span class="brand-title"><?= e($site_nome) ?></span>
          <span class="brand-sub"><?= e($site_subtitulo) ?></span>
        </div>
      </a>

      <!-- Status centralizado "SINAL INTERROMPIDO" -->
      <div class="status-beacon" role="status">
        <span class="status-dot" aria-hidden="true"></span>
        <span>Sinal Interrompido</span>
      </div>

      <!-- Edição do jornal à direita -->
      <div class="header-edition">
        <span>Edição nº <?= e($edicao_numero) ?> · <?= e($edicao_data) ?></span>
      </div>
    </header>

    <!-- 2. CONTEÚDO PRINCIPAL -->
    <main class="scene-body" id="conteudo-principal">
      <div class="scene-grid-inner">
        
        <!-- Bloco editorial à esquerda -->
        <section class="editorial-col" aria-labelledby="heading-404">
          <div class="badge-error-tag">
            <span class="badge-404-pill">404</span>
            <span class="badge-label-tech">Erro de Navegação</span>
          </div>

          <div class="editorial-timestamp">
            Última atualização: agora
          </div>

          <h1 id="heading-404" class="editorial-headline">
            Parece que esta<br>
            notícia se perdeu<br>
            no horizonte.
          </h1>

          <p class="editorial-description">
            A página que você procura mudou de endereço, foi arquivada ou seguiu outro caminho. Nosso farol continua aceso para guiar você de volta.
          </p>

          <a href="<?= BASE_URL ?>/index.php" class="btn-return-home" id="btn-voltar-capa">
            <span>Voltar para a capa</span>
            <span class="btn-arrow-circle" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="5" y1="12" x2="19" y2="12"></line>
                <polyline points="12 5 19 12 12 19"></polyline>
              </svg>
            </span>
          </a>
        </section>

        <!-- Bloco Ilustrativo à direita (Farol, Feixe e Ilha) -->
        <div class="lighthouse-col" aria-hidden="true">
          <div class="lighthouse-stage">
            
            <!-- Lua suave atrás do farol -->
            <div class="moon-halo"></div>

            <!-- Feixe de luz rotativo -->
            <div class="lightbeam-wrap" id="lightbeam">
              <div class="lightbeam-cone"></div>
            </div>

            <!-- Estrutura Geométrica do Farol em SVG -->
            <div class="lighthouse-svg-wrap">
              <svg viewBox="0 0 200 360" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:100%;height:100%">
                <!-- Ilha / Rochedos na base -->
                <path d="M20 340 Q100 315 180 340 L195 360 L5 360 Z" fill="#082229"/>
                <path d="M40 338 Q100 322 160 338 L170 360 L30 360 Z" fill="#0c323c"/>
                
                <!-- Torre: Tronco cônico -->
                <!-- Base branca -->
                <polygon points="62,330 138,330 132,250 68,250" fill="#f8fafc"/>
                <!-- Porta preta na base -->
                <rect x="88" y="275" width="24" height="42" rx="4" fill="#04181f" stroke="#0e3a47" stroke-width="2"/>

                <!-- Faixa Vermelha central -->
                <polygon points="68,250 132,250 126,170 74,170" fill="#be123c"/>

                <!-- Faixa Branca superior com janela redonda -->
                <polygon points="74,170 126,170 120,105 80,105" fill="#f8fafc"/>
                <circle cx="100" cy="138" r="7" fill="#04181f" stroke="#e2e8f0" stroke-width="2"/>

                <!-- Parapeito / Sacada da Lanterna -->
                <rect x="70" y="100" width="60" height="6" rx="2" fill="#0e3a47" stroke="#38bdf8" stroke-width="0.5"/>
                <line x1="74" y1="94" x2="126" y2="94" stroke="#e2e8f0" stroke-width="1.5"/>
                <line x1="80" y1="94" x2="80" y2="100" stroke="#e2e8f0" stroke-width="1.5"/>
                <line x1="100" y1="94" x2="100" y2="100" stroke="#e2e8f0" stroke-width="1.5"/>
                <line x1="120" y1="94" x2="120" y2="100" stroke="#e2e8f0" stroke-width="1.5"/>

                <!-- Lanterna de Vidro Iluminada (Luz Dourada) -->
                <rect x="78" y="58" width="44" height="38" rx="2" fill="#fef08a" class="lantern-glow"/>
                <rect x="80" y="60" width="40" height="34" fill="#fde047" opacity="0.9"/>
                <!-- Pilares da lanterna -->
                <line x1="79" y1="58" x2="79" y2="96" stroke="#04181f" stroke-width="2"/>
                <line x1="92" y1="58" x2="92" y2="96" stroke="#04181f" stroke-width="2"/>
                <line x1="108" y1="58" x2="108" y2="96" stroke="#04181f" stroke-width="2"/>
                <line x1="121" y1="58" x2="121" y2="96" stroke="#04181f" stroke-width="2"/>

                <!-- Telhado Cônico Vermelho -->
                <polygon points="70,58 130,58 100,24" fill="#be123c"/>
                <!-- Cúpula e cata-vento metálico no topo -->
                <circle cx="100" cy="22" r="4" fill="#f59e0b"/>
                <line x1="100" y1="18" x2="100" y2="8" stroke="#f59e0b" stroke-width="2"/>
                <line x1="95" y1="12" x2="105" y2="12" stroke="#f59e0b" stroke-width="2"/>
              </svg>
            </div>

          </div>
        </div>

      </div>
    </main>

    <!-- 3. OCEANO COM ONDAS ANIMADAS -->
    <div class="ocean-container" aria-hidden="true">
      <!-- Onda de Fundo -->
      <svg class="wave wave--back" viewBox="0 0 1440 120" preserveAspectRatio="none">
        <path d="M0,45 C280,15 520,75 800,45 C1080,15 1320,75 1600,45 L1600,120 L0,120 Z" fill="#042028"/>
      </svg>
      <!-- Onda do Meio -->
      <svg class="wave wave--mid" viewBox="0 0 1440 120" preserveAspectRatio="none">
        <path d="M0,60 C320,95 640,25 960,65 C1280,95 1500,35 1800,60 L1800,120 L0,120 Z" fill="#062b35"/>
      </svg>
      <!-- Onda da Frente -->
      <svg class="wave wave--front" viewBox="0 0 1440 120" preserveAspectRatio="none">
        <path d="M0,80 C240,55 480,105 720,80 C960,55 1200,105 1440,80 L1440,120 L0,120 Z" fill="#02141a"/>
      </svg>
    </div>

    <!-- 4. RODAPÉ MINIMALISTA -->
    <footer class="scene-footer">
      <div class="footer-left-guide">
        <span class="guide-line" aria-hidden="true"></span>
        <span class="guide-text">Procure a luz</span>
      </div>

      <div class="footer-right-coords">
        <div class="compass-icon-wrap" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76" fill="rgba(225, 29, 72, 0.4)"></polygon>
          </svg>
        </div>
        <div class="coords-text">
          <span class="coords-title">Rota Desconhecida</span>
          <span class="coords-val">23°33'S · 46°38'W</span>
        </div>
      </div>
    </footer>

  </div>

  <!-- Botão de interrogação flutuante no canto inferior direito -->
  <a href="<?= BASE_URL ?>/index.php" class="floating-help-btn" title="Voltar ao início de O Farol" aria-label="Ajuda">?</a>

  <script>
    // Microinteração Parallax Suave com o Movimento do Mouse
    (function(){
      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

      var scene = document.getElementById('scene-container');
      var giant404 = document.getElementById('giant-404');
      var beam = document.getElementById('lightbeam');

      if (!scene || !giant404) return;

      scene.addEventListener('mousemove', function(e){
        var x = (e.clientX / window.innerWidth) - 0.5;
        var y = (e.clientY / window.innerHeight) - 0.5;

        // Deslocamento sutil no 404 de fundo
        giant404.style.transform = 'translate(calc(-50% + ' + (x * -25) + 'px), calc(-52% + ' + (y * -15) + 'px))';
      });
    })();
  </script>

</body>
</html>
