<?php
/**
 * Prime Dental Clinic Management System
 * Global HTML Footer & JavaScript Scripts
 */
?>
    </div> <!-- Close .main-wrapper -->
</div> <!-- Close .app-wrapper -->

<!-- Core Scripts -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/search.js"></script>

<?php if (isset($extraScripts) && is_array($extraScripts)): ?>
    <?php foreach ($extraScripts as $script): ?>
        <script src="<?= BASE_URL . '/' . ltrim($script, '/') ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
