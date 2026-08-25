(function () {
    'use strict';

    function task(form) {
        var field = form.querySelector('input[name="keleva_task"]');
        return field ? field.value : '';
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.keleva-manager-wrap form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                var currentTask = task(form);
                var message = '';
                if (currentTask === 'update_order_status') {
                    message = 'Confirmez-vous le changement de statut de cette commande ?';
                } else if (currentTask === 'update_appearance_palette') {
                    message = 'Appliquer cette palette au magasin ? Le changement est réversible.';
                } else if (currentTask === 'update_product') {
                    var file = form.querySelector('input[type="file"]');
                    if (file && file.files && file.files[0]) {
                        var selected = file.files[0];
                        if (selected.size > 5 * 1024 * 1024) {
                            event.preventDefault();
                            window.alert('La photo ne doit pas dépasser 5 Mo.');
                            return;
                        }
                        if (!/^image\/(jpeg|png|webp|avif)$/.test(selected.type)) {
                            event.preventDefault();
                            window.alert('Choisissez une image JPG, PNG, WebP ou AVIF.');
                            return;
                        }
                    }
                    message = 'Enregistrer le prix, le stock ou la photo de ce produit ?';
                }
                if (message && !window.confirm(message)) event.preventDefault();
            });
        });
    });
}());
