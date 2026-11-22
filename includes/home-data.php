<?php
/**
 * O FAROL - Dados da capa.
 *
 * Só leitura: nenhuma tabela é alterada. Reúne em um único array tudo o que
 * includes/components/home-reference.php precisa para desenhar a página inicial.
 *
 * Regras editoriais usadas aqui:
 *  - Manchete: a matéria marcada como "manchete_hero" no painel (a mais recente);
 *    sem marcação, a publicação mais recente.
 *  - Destaques: matérias marcadas na capa (Ciência, Jogos, Cultura) vêm primeiro; depois as mais recentes.
 *  - Nada se repete na capa enquanto houver outra matéria disponível.
 *  - Módulos desligados em Admin > Layout da Homepage não geram dados.
 */
declare(strict_types=1);

/** Executa uma consulta preparada e devolve todas as linhas ([] em caso de falha). */
function home_query(PDO $pdo, string $sql, array $params = []): array {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        error_log('O Farol (capa): ' . $e->getMessage());
        return [];
    }
}

/** Primeira linha de uma consulta (a SQL deve ter LIMIT 1) ou null. */
function home_query_one(PDO $pdo, string $sql, array $params = []): ?array {
    $rows = home_query($pdo, $sql, $params);
    return $rows[0] ?? null;
}

/**
 * @param array<string,mixed> $settings Resultado de get_site_settings().
 * @return array<string,mixed>
 */
function load_home_data(?PDO $pdo, array $settings): array {
    $data = [
        'hero'         => null,
        'secondary'    => [],
        'latest'       => [],
        'most_read'    => [],
        'sections'     => [],
        'opiniao'      => null,
        'flagrante'    => null,
        'clube_livro'  => null,
        'processos'    => [],
        'enquete'      => null,
        'enquete_opcoes' => [],
        'jogos_tabela' => [],
        'edicao'       => null,
        'has_news'     => false,
    ];
    if (!$pdo) {
        return $data;
    }

    $on = static fn(string $key): bool => !empty($settings[$key]);

    // Todas as publicações recentes numa só consulta; a capa é montada em PHP a partir delas.
    $pool = home_query($pdo,
        "SELECT n.id, n.titulo, n.subtitulo, n.slug, n.chapeu, n.imagem_capa, n.legenda_capa, n.credito_foto,
                n.tempo_leitura, n.publicado_em, n.posicao_homepage, n.ordem_home, n.visualizacoes,
                c.id AS categoria_id, c.nome AS categoria_nome, c.slug AS categoria_slug,
                c.descricao AS categoria_descricao, c.ordem AS categoria_ordem, c.ativo AS categoria_ativa,
                (SELECT GROUP_CONCAT(a.nome SEPARATOR ' e ') FROM autores a
                   JOIN noticia_autores na ON na.autor_id = a.id WHERE na.noticia_id = n.id) AS autores_nomes
         FROM noticias n
         JOIN categorias c ON c.id = n.categoria_id
         WHERE n.status = 'publicado'
         ORDER BY n.publicado_em DESC, n.id DESC
         LIMIT 150");

    if ($pool) {
        $data['has_news'] = true;

        // Matérias que o painel marcou para um espaço da capa (só conta se o módulo está ligado).
        $slot_module = [
            'ciencia_tecnica'          => 'modulo_ciencia',
            'jogos_esportes'           => 'modulo_jogos',
            'cultura_empreendedorismo' => 'modulo_cultura',
        ];
        $curated = [];
        foreach ($pool as $n) {
            $slot = (string)$n['posicao_homepage'];
            if (isset($slot_module[$slot]) && $on($slot_module[$slot])) {
                $curated[(int)$n['id']] = true;
            }
        }

        $used = [];

        // 1. Manchete
        $hero = null;
        if ($on('modulo_manchete')) {
            foreach ($pool as $n) {
                if ($n['posicao_homepage'] === 'manchete_hero') { $hero = $n; break; }
            }
            $hero = $hero ?? $pool[0];
            $used[(int)$hero['id']] = true;
        }
        $data['hero'] = $hero;

        // Coluna de opinião (espaço próprio, mais abaixo na capa)
        $opiniao = null;
        if ($on('modulo_opiniao')) {
            foreach ($pool as $n) {
                if ($n['posicao_homepage'] === 'coluna_opiniao' && !isset($used[(int)$n['id']])) { $opiniao = $n; break; }
            }
            if ($opiniao) {
                $opiniao['autor'] = home_query_one($pdo,
                    "SELECT a.nome, a.turma, a.foto FROM autores a
                     JOIN noticia_autores na ON a.id = na.autor_id WHERE na.noticia_id = ? LIMIT 1",
                    [(int)$opiniao['id']]);
                $used[(int)$opiniao['id']] = true;
            }
        }
        $data['opiniao'] = $opiniao;

        // 2. Notícias secundárias (3): marcadas na capa primeiro, depois as mais recentes
        if ($on('modulo_destaques')) {
            $candidates = array_values(array_filter($pool, static fn(array $n): bool => !isset($used[(int)$n['id']])));
            usort($candidates, static function (array $a, array $b) use ($curated): int {
                $ra = isset($curated[(int)$a['id']]) ? 0 : 1;
                $rb = isset($curated[(int)$b['id']]) ? 0 : 1;
                if ($ra !== $rb) return $ra <=> $rb;
                if ($ra === 0) return ((int)$a['ordem_home']) <=> ((int)$b['ordem_home']);
                return 0; // usort é estável no PHP 8: mantém a ordem cronológica
            });
            $data['secondary'] = array_slice($candidates, 0, 3);
            foreach ($data['secondary'] as $n) { $used[(int)$n['id']] = true; }

            // 3. Últimas notícias (7): as mais recentes ainda não exibidas.
            // Com poucas publicações, aceita repetir as destacadas acima (nunca a manchete).
            $unused = array_values(array_filter($pool, static fn(array $n): bool => !isset($used[(int)$n['id']])));
            if (count($unused) >= 4) {
                $data['latest'] = array_slice($unused, 0, 7);
            } else {
                $heroId = $hero ? (int)$hero['id'] : 0;
                $data['latest'] = array_slice(array_values(array_filter(
                    $pool, static fn(array $n): bool => (int)$n['id'] !== $heroId
                )), 0, 7);
            }
            foreach ($data['latest'] as $n) { $used[(int)$n['id']] = true; }
        }

        // 4. Seções por categoria (só categorias ativas que têm publicações)
        $totals = [];
        foreach (home_query($pdo, "SELECT categoria_id, COUNT(*) AS total FROM noticias WHERE status = 'publicado' GROUP BY categoria_id") as $row) {
            $totals[(int)$row['categoria_id']] = (int)$row['total'];
        }
        $by_category = [];
        foreach ($pool as $n) {
            if ((int)$n['categoria_ativa'] !== 1) continue;
            $by_category[(int)$n['categoria_id']][] = $n;
        }
        $sections = [];
        foreach ($by_category as $cat_id => $items) {
            // Marcadas e ainda não exibidas primeiro; mantém a ordem cronológica dentro de cada grupo.
            usort($items, static function (array $a, array $b) use ($used, $curated): int {
                $rank = static fn(array $n): int =>
                    (isset($used[(int)$n['id']]) ? 2 : 0) + (isset($curated[(int)$n['id']]) ? 0 : 1);
                return $rank($a) <=> $rank($b);
            });
            $first = $items[0];
            $sections[] = [
                'id'        => $cat_id,
                'nome'      => (string)$first['categoria_nome'],
                'slug'      => (string)$first['categoria_slug'],
                'descricao' => (string)($first['categoria_descricao'] ?? ''),
                'ordem'     => (int)$first['categoria_ordem'],
                'total'     => $totals[$cat_id] ?? count($items),
                'items'     => array_slice($items, 0, 5),
            ];
        }
        usort($sections, static fn(array $a, array $b): int => $a['ordem'] <=> $b['ordem']);
        $data['sections'] = array_slice($sections, 0, 6);
    }

    // Mais lidas: só com leituras registradas de verdade
    $data['most_read'] = home_query($pdo,
        "SELECT n.id, n.titulo, n.slug, n.visualizacoes, n.publicado_em, c.nome AS categoria_nome
         FROM noticias n JOIN categorias c ON c.id = n.categoria_id
         WHERE n.status = 'publicado' AND n.visualizacoes > 0
         ORDER BY n.visualizacoes DESC, n.publicado_em DESC LIMIT 5");

    // Flagrante da semana (acompanha a coluna de opinião)
    if ($on('modulo_opiniao')) {
        $data['flagrante'] = home_query_one($pdo, "SELECT * FROM flagrantes_semana WHERE destaque = 1 ORDER BY id DESC LIMIT 1");
    }

    // Jogos: quadro de placares
    if ($on('modulo_jogos')) {
        $data['jogos_tabela'] = home_query($pdo, "SELECT * FROM jogos_resultados WHERE destaque_home = 1 ORDER BY ordem ASC LIMIT 4");
    }

    if ($on('modulo_clube_livro')) {
        $data['clube_livro'] = home_query_one($pdo, "SELECT * FROM clube_livro WHERE ativo = 1 ORDER BY id DESC LIMIT 1");
    }

    if ($on('modulo_processos')) {
        $data['processos'] = home_query($pdo, "SELECT * FROM processos_seletivos WHERE ativo = 1 ORDER BY ordem ASC LIMIT 3");
    }

    if ($on('modulo_enquete')) {
        $enquete = home_query_one($pdo, "SELECT * FROM enquetes WHERE ativo = 1 ORDER BY destaque_sidebar DESC, id DESC LIMIT 1");
        if ($enquete) {
            $data['enquete'] = $enquete;
            $data['enquete_opcoes'] = home_query($pdo, "SELECT * FROM enquete_opcoes WHERE enquete_id = ? ORDER BY ordem ASC", [(int)$enquete['id']]);
        }
    }

    // Publicação institucional: edição em destaque
    $data['edicao'] = home_query_one($pdo, "SELECT numero, titulo, mes_ano, capa_url, pdf_url FROM edicoes WHERE destaque = 1 ORDER BY numero DESC LIMIT 1");

    return $data;
}
