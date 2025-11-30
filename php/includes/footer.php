    </main>

    <?php if (isLoggedIn()): ?>
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container text-center">
            <span class="text-muted">
                &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - Retail Management System v<?php echo APP_VERSION; ?>
            </span>
        </div>
    </footer>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body>
</html>
