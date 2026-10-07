        </main>
        <footer class="rodape"><?= e(APP_NOME) ?> &copy; <?= date('Y') ?> - Projeto de Desenvolvimento de Sistemas</footer>
    </div>
</div>

<div id="toasts" class="toasts"></div>

<script>
    // Dados do usuário logado disponíveis para o JavaScript
    window.USUARIO = <?= json_encode(usuario_logado(), JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="assets/js/app.js"></script>
<?php foreach ($scripts ?? [] as $script): ?>
    <script src="assets/js/<?= e($script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
