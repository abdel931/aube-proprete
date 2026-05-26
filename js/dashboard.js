/* ============================================
   DASHBOARD — Interactions tableau de bord
   ============================================ */
document.addEventListener('DOMContentLoaded', function() {

    // Marquer le lien actif dans la sidebar
    const liens = document.querySelectorAll('.sidebar-nav a');
    const pageCourante = window.location.pathname;

    liens.forEach(function(lien) {
        lien.classList.remove('active');
        if (pageCourante.includes(lien.getAttribute('href'))) {
            lien.classList.add('active');
        }
    });

    console.log('✅ Dashboard chargé');
});