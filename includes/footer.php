</main>
<footer class="site-footer">
    <div class="shell footer-content">
        <div>
            <a class="brand brand-footer" href="<?= e($basePath ?? '') ?>index.php">
                <span class="brand-mark" aria-hidden="true">E</span>
                <span>EventHub</span>
            </a>
            <p>Locații potrivite pentru evenimente memorabile.</p>
        </div>
        <div>
            <p class="footer-heading">Explorează</p>
            <a href="<?= e($basePath ?? '') ?>venues.php">Toate locațiile</a>
        </div>
        <p class="copyright">&copy; <?= date('Y') ?> EventHub. Proiect universitar DAW.</p>
    </div>
</footer>
<script src="<?= e($basePath ?? '') ?>assets/js/app.js"></script>
</body>
</html>
