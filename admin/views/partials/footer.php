        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle mobile
        document.getElementById('sidebarToggle')?.addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('show');
        });
        
        // Confirmation des suppressions
        document.querySelectorAll('[data-confirm]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                if (!confirm(btn.dataset.confirm)) {
                    e.preventDefault();
                }
            });
        });
        
        // Auto-generate slug from title
        document.querySelectorAll('[data-slug-source]').forEach(input => {
            const source = document.querySelector(input.dataset.slugSource);
            const target = input;
            const originalValue = target.value;
            
            source?.addEventListener('input', () => {
                if (!originalValue || target.dataset.slugModified !== 'true') {
                    target.value = source.value
                        .toLowerCase()
                        .replace(/[^\w\s-]/g, '')
                        .replace(/\s+/g, '-')
                        .substring(0, 50);
                }
            });
            
            target.addEventListener('input', () => {
                target.dataset.slugModified = 'true';
            });
        });
    </script>
</body>
</html>