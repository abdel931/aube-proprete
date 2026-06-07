document.addEventListener('DOMContentLoaded', function() {

    // Marquer lien actif sidebar
    const liens = document.querySelectorAll('.sidebar-nav a');
    const pageCourante = window.location.pathname;
    liens.forEach(function(lien) {
        lien.classList.remove('active');
        if (pageCourante.includes(lien.getAttribute('href').replace('./', ''))) {
            lien.classList.add('active');
        }
    });

    // Charger les données du client connecté
    fetch('http://localhost/aube-proprete/php/get-dashboard.php')
    .then(r => r.json())
    .then(function(data) {
        if (!data.succes) {
            window.location.href = '/aube-proprete/espace-client/login.html';
            return;
        }

        const c = data.client;
        const s = data.stats;

        // Initiales et nom
        const initiales = c.prenom[0].toUpperCase() + c.nom[0].toUpperCase();
        document.getElementById('user-initials').textContent = initiales;
        document.getElementById('user-name').textContent = c.prenom + ' ' + c.nom;

        // Stats
        document.getElementById('nb-interventions').textContent = s.total_interventions;
        document.getElementById('nb-terminees').textContent = s.terminees;
        document.getElementById('total-factures').textContent = s.total_factures + '€';

        // Prochaine intervention
        const zoneProchaine = document.getElementById('prochaine-intervention');
        if (s.prochaine) {
            const date = new Date(s.prochaine.date_intervention).toLocaleDateString('fr-FR', {weekday:'long', day:'numeric', month:'long', year:'numeric'});
            zoneProchaine.innerHTML = `
                <div class="next-intervention">
                    <div>
                        <div class="next-intervention-label">Prochaine intervention</div>
                        <div class="next-intervention-type">${s.prochaine.type_service}</div>
                        <div class="next-intervention-date">📅 ${date} · ${s.prochaine.heure}</div>
                    </div>
                    <div class="next-intervention-icon">🧹</div>
                </div>`;
        } else {
            zoneProchaine.innerHTML = `<div style="background:#eef6ff;border-radius:8px;padding:14px;font-size:13px;color:#3a6a99;margin-bottom:12px;">Aucune intervention planifiée. <a href="rdv.html" style="color:#1a6bbf;font-weight:600;">Prendre RDV →</a></div>`;
        }

        // Liste interventions
        const listeInter = document.getElementById('liste-interventions');
        if (data.interventions.length === 0) {
            listeInter.innerHTML = '<p style="font-size:13px;color:#3a6a99;">Aucune intervention pour le moment.</p>';
        } else {
            listeInter.innerHTML = data.interventions.slice(0,3).map(function(i) {
                const date = new Date(i.date_intervention).toLocaleDateString('fr-FR', {day:'numeric', month:'long', year:'numeric'});
                const badges = {
                    'planifiee': 'statut-planifiee',
                    'terminee': 'statut-terminee',
                    'annulee': 'statut-annulee'
                };
                const labels = {
                    'planifiee': 'Planifiée',
                    'terminee': 'Terminée',
                    'annulee': 'Annulée'
                };
                return `
                    <div class="intervention-row">
                        <div class="intervention-info">
                            <div class="intervention-type">${i.type_service}</div>
                            <div class="intervention-date">${date}</div>
                        </div>
                        <span class="statut-badge ${badges[i.statut] || ''}">${labels[i.statut] || i.statut}</span>
                    </div>`;
            }).join('');
        }

        // Factures
        const listeFac = document.getElementById('liste-factures');
        if (data.factures.length === 0) {
            listeFac.innerHTML = '<p style="font-size:13px;color:#3a6a99;">Aucune facture pour le moment.</p>';
        } else {
            listeFac.innerHTML = data.factures.slice(0,3).map(function(f, i) {
                const num = 'FAC-2026-00' + (i+1);
                const badges = {'payee':'statut-payee','en_attente':'statut-en-attente','annulee':'statut-annulee'};
                const labels = {'payee':'Payée','en_attente':'En attente','annulee':'Annulée'};
                return `
                    <div class="facture-row">
                        <div>
                            <div class="facture-num">${num}</div>
                            <div class="facture-montant">${parseFloat(f.montant).toFixed(2)} €</div>
                        </div>
                        <span class="statut-badge ${badges[f.statut] || ''}">${labels[f.statut] || f.statut}</span>
                    </div>`;
            }).join('');
        }
    })
    .catch(function() {
        window.location.href = '/aube-proprete/espace-client/login.html';
    });
});