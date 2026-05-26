document.addEventListener('DOMContentLoaded', function() {

    // Toggle mot de passe
    const toggleMdp = document.getElementById('toggle-mdp');
    const inputMdp  = document.getElementById('mdp');
    if (toggleMdp && inputMdp) {
        toggleMdp.addEventListener('click', function() {
            inputMdp.type = inputMdp.type === 'password' ? 'text' : 'password';
            toggleMdp.textContent = inputMdp.type === 'password' ? '👁️' : '🙈';
        });
    }

    const form = document.getElementById('inscription-form');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const prenom      = form.querySelector('[name="prenom"]').value.trim();
        const nom         = form.querySelector('[name="nom"]').value.trim();
        const email       = form.querySelector('[name="email"]').value.trim();
        const telephone   = form.querySelector('[name="telephone"]').value.trim();
        const mdp         = form.querySelector('[name="mot_de_passe"]').value;
        const confirm     = form.querySelector('[name="confirmation"]').value;

        if (prenom.length < 2 || nom.length < 2) {
            afficherMessageAuth('❌ Prénom et nom invalides.', 'erreur'); return;
        }
        if (!email.includes('@')) {
            afficherMessageAuth('❌ Email invalide.', 'erreur'); return;
        }
        if (mdp.length < 8) {
            afficherMessageAuth('❌ Mot de passe trop court.', 'erreur'); return;
        }
        if (mdp !== confirm) {
            afficherMessageAuth('❌ Les mots de passe ne correspondent pas.', 'erreur'); return;
        }

        const btn = form.querySelector('.btn-auth');
        btn.textContent = 'Création en cours...';
        btn.disabled = true;

        const formData = new FormData();
        formData.append('prenom', prenom);
        formData.append('nom', nom);
        formData.append('email', email);
        formData.append('telephone', telephone);
        formData.append('mot_de_passe', mdp);
        formData.append('confirmation', confirm);

        fetch('http://localhost/aube-proprete/php/inscription.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(function(data) {
            if (data.succes) {
                afficherMessageAuth('✅ ' + data.message, 'succes');
                setTimeout(() => { window.location.href = data.redirect; }, 1000);
            } else {
                afficherMessageAuth('❌ ' + data.message, 'erreur');
            }
        })
        .catch(function() {
            afficherMessageAuth('❌ Erreur réseau.', 'erreur');
        })
        .finally(function() {
            btn.textContent = 'Créer mon compte';
            btn.disabled = false;
        });
    });
});

function afficherMessageAuth(texte, type) {
    const zone = document.getElementById('auth-message');
    if (!zone) return;
    zone.innerHTML = `<div class="auth-message-${type}">${texte}</div>`;
}