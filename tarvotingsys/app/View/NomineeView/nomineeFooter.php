</main>
</div>
    <script>
    // ensure Bootstrap flex layout on body so footer stays at the bottom
    document.addEventListener('DOMContentLoaded', function(){
        document.body.classList.add('d-flex', 'flex-column', 'min-vh-100');
    });
    </script>

    <footer class="mt-auto bg-warning py-3">
      <div class="container text-center">
        Developed by <strong>Tunku Abdul Rahman University of Management and Technology</strong> &middot;
        Copyright &copy; <?= date('Y') ?>
      </div>
    </footer>

</body>
</html>