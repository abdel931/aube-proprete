/* ============================================
   AUBE PROPRETÉ SERVICES — JavaScript principal
   Auteur : Abdel-Rahmane — Stage IPSSI Paris
   Date   : 2026
   ============================================ */

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Aube Propreté Services — JS chargé');
    initMenuBurger();
    initScrollAnimations();
    initNavbarScroll();
    initCompteurs();
    initFormulaire();
});

/* ============================================
   1. MENU BURGER
   ============================================ */
function initMenuBurger() {
    const burger = document.getElementById('burger');
    const navbarLinks = document.querySelector('.navbar-links');
    if (!burger || !navbarLinks) return;

    burger.addEventListener('click', function() {
        navbarLinks.classList.toggle('open');
        burger.classList.toggle('active');
    });

    navbarLinks.querySelectorAll('a').forEach(function(lien) {
        lien.addEventListener('click', function() {
            navbarLinks.classList.remove('open');
            burger.classList.remove('active');
        });
    });

    document.addEventListener('click', function(e) {
        if (!burger.contains(e.target) && !navbarLinks.contains(e.target)) {
            navbarLinks.classList.remove('open');
            burger.classList.remove('active');
        }
    });
}

/* ============================================
   2. ANIMATIONS AU SCROLL
   ============================================ */
function initScrollAnimations() {
    const elementsAAnimer = document.querySelectorAll(
        '.service-card, .tarif-card, .temoignage-card, ' +
        '.process-step, .about-content, .faq-item, ' +
        '.contact-info-item, .partenaire-item'
    );

    elementsAAnimer.forEach(function(element) {
        element.classList.add('anim-hidden');
    });

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('anim-visible');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });

    elementsAAnimer.forEach(function(element) {
        observer.observe(element);
    });
}

/* ============================================
   3. NAVBAR SCROLL
   ============================================ */
function initNavbarScroll() {
    const navbar = document.querySelector('.navbar');
    if (!navbar) return;

    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
}

/* ============================================
   4. COMPTEURS ANIMÉS
   ============================================ */
function initCompteurs() {
    const statsBar = document.querySelector('.stats-bar');
    if (!statsBar) return;

    let dejaAnime = false;

    const observer = new IntersectionObserver(function(entries) {
        if (entries[0].isIntersecting && !dejaAnime) {
            dejaAnime = true;

            document.querySelectorAll('.stat-number').forEach(function(element) {
                const texte = element.textContent;
                const nombre = parseInt(texte);
                if (isNaN(nombre)) return;

                let compteur = 0;
                const increment = Math.ceil(nombre / 60);
                const prefixe = texte.includes('+') ? '+' : '';
                const suffixe = texte.includes('%') ? '%' : '';

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
   ============================================ */
function initFormulaire() {
    const form = document.getElementById('contact-form');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const nom        = form.querySelector('[name="nom"]').value.trim();
        const email      = form.querySelector('[name="email"]').value.trim();
        const telephone  = form.querySelector('[name="telephone"]').value.trim();
        const prestation = form.querySelector('[name="prestation"]').value;
        const message    = form.querySelector('[name="message"]').value.trim();

        if (nom.length < 2)          { afficherMessage('❌ Nom invalide.', 'erreur'); return; }
        if (!validerEmail(email))    { afficherMessage('❌ Email invalide.', 'erreur'); return; }
        if (telephone.length < 10)   { afficherMessage('❌ Téléphone invalide.', 'erreur'); return; }
        if (!prestation)             { afficherMessage('❌ Choisissez une prestation.', 'erreur'); return; }
        if (message.length < 10)     { afficherMessage('❌ Message trop court (min 10 caractères).', 'erreur'); return; }

        const btn = form.querySelector('.btn-submit');
        btn.textContent = 'Envoi en cours...';
        btn.disabled = true;

        const formData = new FormData();
        formData.append('nom', nom);
        formData.append('email', email);
        formData.append('telephone', telephone);
        formData.append('prestation', prestation);
        formData.append('message', message);

        fetch('http://localhost/aube-proprete/php/contact.php', {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.succes) {
                afficherMessage('✅ ' + data.message, 'succes');
                form.reset();
            } else {
                afficherMessage('❌ ' + data.message, 'erreur');
            }
        })
        .catch(function() {
            afficherMessage('❌ Erreur réseau. Vérifiez que XAMPP est lancé.', 'erreur');
        })
        .finally(function() {
            btn.textContent = 'Envoyer ma demande';
            btn.disabled = false;
        });
    });
}

/* ============================================
   FONCTIONS UTILITAIRES
   ============================================ */
function validerEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

function afficherMessage(texte, type) {
    const ancien = document.querySelector('.form-message');
    if (ancien) ancien.remove();

    const msg = document.createElement('div');
    msg.className = 'form-message form-message-' + type;
    msg.textContent = texte;

    const form = document.getElementById('contact-form');
    form.parentNode.insertBefore(msg, form.nextSibling);

    setTimeout(function() { msg.remove(); }, 5000);
}