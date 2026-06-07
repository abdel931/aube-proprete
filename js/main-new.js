window.onload = function() {
    var form = document.getElementById('contact-form');
    if (!form) return;
    
    form.onsubmit = function(e) {
        e.preventDefault();
        
        var nom = document.querySelector('[name="nom"]').value.trim();
        var email = document.querySelector('[name="email"]').value.trim();
        var telephone = document.querySelector('[name="telephone"]').value.trim();
        var prestation = document.querySelector('[name="prestation"]').value;
        var message = document.querySelector('[name="message"]').value.trim();
        var btn = document.querySelector('.btn-submit');
        
        if (!nom || !email || !telephone || !prestation || !message) {
            alert('Veuillez remplir tous les champs.');
            return;
        }
        
        btn.textContent = 'Envoi...';
        btn.disabled = true;
        
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'http://localhost/aube-proprete/php/contact.php');
        xhr.onload = function() {
            try {
                var data = JSON.parse(xhr.responseText);
                alert(data.message);
                if (data.succes) form.reset();
            } catch(err) {
                alert('Reponse invalide du serveur');
            }
            btn.textContent = 'Envoyer ma demande';
            btn.disabled = false;
        };
        xhr.onerror = function() {
            alert('Erreur reseau. XAMPP est-il lance ?');
            btn.textContent = 'Envoyer ma demande';
            btn.disabled = false;
        };
        
        var formData = new FormData();
        formData.append('nom', nom);
        formData.append('email', email);
        formData.append('telephone', telephone);
        formData.append('prestation', prestation);
        formData.append('message', message);
        xhr.send(formData);
    };
};