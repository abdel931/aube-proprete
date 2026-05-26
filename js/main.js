/* ============================================
   AUBE PROPRETÉ SERVICES — JavaScript principal
   Auteur : Abdel-Rahmane — Stage IPSSI Paris
   Date   : 2026
   ============================================ */

/* On attend que toute la page soit chargée
   avant d'exécuter le moindre code JavaScript */
document.addEventListener('DOMContentLoaded', function() {

    console.log('✅ Aube Propreté Services — JS chargé');

    // On appelle toutes nos fonctions ici
    initMenuBurger();
    initScrollAnimations();
    initNavbarScroll();
    initCompteurs();
    initFormulaire();

});

/* ============================================
   1. MENU BURGER — Navigation mobile
   ============================================ */
function initMenuBurger() {

    // On récupère les éléments HTML dont on a besoin
    const burger = document.getElementById('burger');
    const navbarLinks = document.querySelector('.navbar-links');

    // Sécurité : si ces éléments n'existent pas, on arrête
    if (!burger || !navbarLinks) return;

    // Quand on clique sur le burger
    burger.addEventListener('click', function() {

        // On ajoute ou enlève la classe "open" sur le menu
        navbarLinks.classList.toggle('open');

        // On ajoute ou enlève la classe "active" sur le burger
        burger.classList.toggle('active');

    });

    // Fermer le menu quand on clique sur un lien
    const liens = navbarLinks.querySelectorAll('a');
    liens.forEach(function(lien) {
        lien.addEventListener('click', function() {
            navbarLinks.classList.remove('open');
            burger.classList.remove('active');
        });
    });

    // Fermer le menu si on clique en dehors
    document.addEventListener('click', function(e) {
        if (!burger.contains(e.target) && !navbarLinks.contains(e.target)) {
            navbarLinks.classList.remove('open');
            burger.classList.remove('active');
        }
    });
}

/* ============================================
   2. ANIMATIONS AU SCROLL
   Les éléments apparaissent en douceur
   quand on fait défiler la page
   ============================================ */
function initScrollAnimations() {

    // On sélectionne tous les éléments à animer
    const elementsAAnimer = document.querySelectorAll(
        '.service-card, .tarif-card, .temoignage-card, ' +
        '.process-step, .about-content, .faq-item, ' +
        '.contact-info-item, .partenaire-item'
    );

    // On ajoute la classe de base "hidden" à chaque élément
    elementsAAnimer.forEach(function(element) {
        element.classList.add('anim-hidden');
    });

    // IntersectionObserver : surveille quand un élément
    // entre dans la zone visible de l'écran
    const observer = new IntersectionObserver(function(entries) {

        entries.forEach(function(entry) {

            // Si l'élément est visible à l'écran
            if (entry.isIntersecting) {

                // On lui ajoute la classe "visible"
                entry.target.classList.add('anim-visible');

                // On arrête de surveiller cet élément
                // (l'animation ne se rejoue qu'une fois)
                observer.unobserve(entry.target);
            }
        });

    }, {
        threshold: 0.1,    // Déclenche quand 10% de l'élément est visible
        rootMargin: '0px 0px -50px 0px'  // Déclenche 50px avant le bas de l'écran
    });

    // On demande à l'observer de surveiller chaque élément
    elementsAAnimer.forEach(function(element) {
        observer.observe(element);
    });
}

/* ============================================
   3. NAVBAR — Effet au défilement
   La navbar change de style quand on scrolle
   ============================================ */
function initNavbarScroll() {

    const navbar = document.querySelector('.navbar');
    if (!navbar) return;

    window.addEventListener('scroll', function() {

        // Si on a scrollé plus de 50px depuis le haut
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
}

/* ============================================
   4. COMPTEURS ANIMÉS — Stats bar
   Les chiffres comptent depuis 0 jusqu'à
   leur valeur finale
   ============================================ */
function initCompteurs() {

    const statsBar = document.querySelector('.stats-bar');
    if (!statsBar) return;

    let dejaAnime = false;

    // Observer qui surveille la stats bar
    const observer = new IntersectionObserver(function(entries) {

        if (entries[0].isIntersecting && !dejaAnime) {

            dejaAnime = true; // N'anime qu'une seule fois

            // On récupère tous les chiffres
            const nombres = document.querySelectorAll('.stat-number');

            nombres.forEach(function(element) {

                // On lit la valeur cible depuis le HTML
                const texte = element.textContent;
                const nombre = parseInt(texte);

                // Si ce n'est pas un nombre (ex: "10 ans"), on ignore
                if (isNaN(nombre)) return;

                let compteur = 0;
                const increment = Math.ceil(nombre / 60);
                const prefixe = texte.includes('+') ? '+' : '';
                const suffixe = texte.includes('%') ? '%' : '';

                // Intervalle qui incrémente le compteur
                const intervalle = setInterval(function() {
                    compteur += increment;
                    if (compteur >= nombre) {
                        compteur = nombre;
                        clearInterval(intervalle);
                    }
                    element.textContent = prefixe + compteur + suffixe;
                }, 30);
            });
        }
    }, { threshold: 0.5 });

    observer.observe(statsBar);
}

/* ============================================
   5. FORMULAIRE DE CONTACT
   Envoi des données vers le PHP via fetch()
   ============================================ */
function initFormulaire() {

    const form = document.getElementById('contact-form');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const nom       = form.querySelector('[name="nom"]').value.trim();
        const email     = form.querySelector('[name="email"]').value.trim();
        const telephone = form.querySelector('[name="telephone"]').value.trim();
        const prestation = form.querySelector('[name="prestation"]').value;
        const message   = form.querySelector('[name="message"]').value.trim();

        // Validations JS
        if (nom.length < 2) { afficherMessage('❌ Nom invalide.', 'erreur'); return; }
        if (!validerEmail(email)) { afficherMessage('❌ Email invalide.', 'erreur'); return; }
        if (telephone.length < 10) { afficherMessage('❌ Téléphone invalide.', 'erreur'); return; }
        if (!prestation) { afficherMessage('❌ Choisissez une prestation.', 'erreur'); return; }
        if (message.length < 10) { afficherMessage('❌ Message trop court.', 'erreur'); return; }

        // On désactive le bouton pendant l'envoi
        const btnSubmit = form.querySelector('.btn-submit');
        btnSubmit.textContent = 'Envoi en cours...';
        btnSubmit.disabled = true;

        // Préparation des données à envoyer
        const formData = new FormData();
        formData.append('nom', nom);
        formData.append('email', email);
        formData.append('telephone', telephone);
        formData.append('prestation', prestation);
        formData.append('message', message);

        // Envoi vers le PHP avec fetch()
        fetch('http://localhost/aube-proprete/php/contact.php', {
            method: 'POST',
            body: formData
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.succes) {
                afficherMessage('✅ ' + data.message, 'succes');
                form.reset();
            } else {
                afficherMessage('❌ ' + data.message, 'erreur');
            }
        })
        .catch(function() {
            afficherMessage('❌ Erreur réseau. Vérifiez votre connexion.', 'erreur');
        })
        .finally(function() {
            btnSubmit.textContent = 'Envoyer ma demande';
            btnSubmit.disabled = false;
        });
    });
}