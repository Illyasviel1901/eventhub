<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/analytics.php';

requireRole('ADMIN', '../login.php');
recordPageVisit();

$summary = analyticsSummary();
$topPages = mostVisitedPages();
$dailyVisits = visitsByDay();
$recentVisits = recentPageVisits();
$maximumDailyVisits = max(1, ...array_column($dailyVisits, 'visits'));

$pageTitle = 'Statistici';
$pageDescription = 'Statistici interne privind accesările paginilor EventHub.';
$currentPage = 'analytics';
$basePath = '../';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="admin-page-heading">
    <div class="shell heading-row">
        <div>
            <p class="eyebrow">Administrare</p>
            <h1>Statistici</h1>
            <p>Statistici interne bazate exclusiv pe pagina și momentul accesării, fără IP sau date personale.</p>
        </div>
        <a class="button button-secondary" href="index.php">Înapoi la dashboard</a>
    </div>
</section>
<section class="section admin-section">
    <div class="shell">
        <div class="admin-stats" aria-label="Rezumat accesări">
            <article class="stat-card"><span>Total accesări</span><strong><?= $summary['total'] ?></strong><p>Toate paginile urmărite</p></article>
            <article class="stat-card"><span>Astăzi</span><strong><?= $summary['today'] ?></strong><p>Accesări în ziua curentă</p></article>
            <article class="stat-card"><span>Ultimele 7 zile</span><strong><?= $summary['last_seven_days'] ?></strong><p>Inclusiv ziua curentă</p></article>
            <article class="stat-card"><span>Pagini distincte</span><strong><?= $summary['tracked_pages'] ?></strong><p>Pagini care au fost accesate</p></article>
        </div>

        <div class="analytics-grid">
            <article class="analytics-panel">
                <div class="analytics-heading"><div><p class="eyebrow">Evoluție</p><h2>Accesări în ultimele 7 zile</h2></div></div>
                <div class="bar-chart" role="img" aria-label="Grafic cu accesările din ultimele 7 zile">
                    <?php foreach ($dailyVisits as $day): ?>
                        <?php $height = $day['visits'] === 0 ? 0 : max(8, (int) round($day['visits'] / $maximumDailyVisits * 100)); ?>
                        <div class="bar-column">
                            <span class="bar-value"><?= $day['visits'] ?></span>
                            <div class="bar-track"><span class="bar-fill" style="height: <?= $height ?>%"></span></div>
                            <time datetime="<?= e($day['date']) ?>"><?= e((new DateTimeImmutable($day['date']))->format('d.m')) ?></time>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>

            <article class="analytics-panel">
                <div class="analytics-heading"><div><p class="eyebrow">Popularitate</p><h2>Cele mai accesate pagini</h2></div></div>
                <?php if ($topPages === []): ?>
                    <p class="analytics-empty">Nu există încă accesări înregistrate.</p>
                <?php else: ?>
                    <ol class="ranking-list">
                        <?php foreach ($topPages as $position => $item): ?>
                            <li><span class="ranking-position"><?= $position + 1 ?></span><span><strong><?= e(analyticsPageLabel($item['page'])) ?></strong><small><?= e($item['page']) ?></small></span><b><?= $item['visits'] ?></b></li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </article>
        </div>

        <section class="analytics-recent" aria-labelledby="recent-visits-title">
            <div class="analytics-heading"><div><p class="eyebrow">Activitate</p><h2 id="recent-visits-title">Accesări recente</h2></div></div>
            <?php if ($recentVisits === []): ?>
                <div class="empty-state"><p>Nu există încă accesări înregistrate.</p></div>
            <?php else: ?>
                <div class="table-wrap"><table class="admin-table"><thead><tr><th>Pagină</th><th>Momentul accesării</th></tr></thead><tbody>
                    <?php foreach ($recentVisits as $visit): ?><tr><td><strong><?= e(analyticsPageLabel($visit['page'])) ?></strong><span class="table-sub-link"><?= e($visit['page']) ?></span></td><td><time datetime="<?= e($visit['visited_at']) ?>"><?= e((new DateTimeImmutable($visit['visited_at']))->format('d.m.Y, H:i:s')) ?></time></td></tr><?php endforeach; ?>
                </tbody></table></div>
            <?php endif; ?>
        </section>
    </div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
