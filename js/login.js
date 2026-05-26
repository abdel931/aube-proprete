/* ============================================
   LOGIN — Gestion de la connexion client
   ============================================ */
document.addEventListener('DOMContentLoaded', function() {

    // Bouton afficher/masquer mot de passe
    const togglePassword = document.getElementById('toggle-password');
    const inputPassword  = document.getElementById('mot-de-passe');

    if (togglePassword && inputPassword) {
        togglePassword.addEventListener('click', function() {
            if (inputPassword.type === 'password') {
                inputPassword.type = 'text';
                togglePassword.textContent = '🙈';
            } else {
                inputPassword.type = 'password';
                togglePassword.textContent = '👁️';
            }
        });
    }

    // Formulaire de connexion
    const form = document.getElementById('login-form');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const email       = form.querySelector('[name="email"]').value.trim();
        const motDePasse  = form.querySelector('[name="mot_de_passe"]').value;
        const btn         = form.querySelector('.btn-auth');

        if (!email || !motDePasse) {
            afficherMessageAuth('❌ Veuillez remplir tous les champs.', 'erreur');
            return;
        }

        btn.textContent = 'Connexion en cours...';
        btn.disabled = true;

        const formData = new FormData();
        formData.append('email', email);
        formData.append('mot_de_passe', motDePasse);

        fetch('/aube-proprete/php/login.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(function(data) {
            if (data.succes) {
                afficherMessageAuth('✅ ' + data.message, 'succes');
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1000);
            } else {
                afficherMessageAuth('❌ ' + data.message, 'erreur');
            }
        })
        .catch(function() {
            afficherMessageAuth('❌ Erreur réseau.', 'erreur');
        })
        .finally(function() {
            btn.textContent = 'Se connecter';
            btn.disabled = false;
        });
    });
});

function afficherMessageAuth(texte, type) {
    const zone = document.getElementById('auth-message');
    if (!zone) return;
    zone.innerHTML = `<div class="auth-message-${type}">${texte}</div>`;
}