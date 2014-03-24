
/**
 * Données d'affichage du <textarea> : bordure en rouge quand modifié, en vert quand mis à jour
 * Bouton "recopier" pour permettre de recopier tout le texte dans le <textarea>
 */
(function (d, $) {
    var form = d.getElementById('translate_update');
    if (form) {
        var list_validators = d.getElementsByClassName('validate_translation'),
            number_of_elements = list_validators.length,
            list_copy_buttons = d.getElementsByClassName('recopy_message'),
            //list_copy_buttons = d.getElementsByClassName('check_translations'),
            ajax_datas = {//Les données de base à envoyer à la fonction $.ajax()
                "data": {},
                "url": form.action,
                "type":"post",
                "dataType": "json",
                "success": function(msg){console.info(msg);}
            },
            func_copy_initial_content = function() {
            //Bouton permettant de recopier le contenu initial de l'expression à traduire
                var id = this.getAttribute('data-target-item'),
                    target_textarea = d.getElementById(id),
                    source_id = this.getAttribute('data-source-id') ? this.getAttribute('data-source-id') : this.getAttribute('data-target-item'),
                    source_content = d.querySelector('[data-token="'+source_id.replace('translation_','')+'"]').innerHTML;
                if (!target_textarea.value ||
                    (target_textarea.value && confirm(message_replace_content))) {
                    target_textarea.value = source_content
                        .replace(/&gt;/g,'>')
                        .replace(/&lt;/g,'<')
                        .replace(/&#0*39;|&quot;/gi,'"')
                        .replace(/&amp;/g,'&');
                    target_textarea.innerHTML = target_textarea.value;
                    if (target_textarea.value && this.nextElementSibling && this.nextElementSibling.classList.has('validate_translation')) {
                        if (this.nextElementSibling.click) {
                            this.nextElementSibling.click();
                        } else {
                            this.nextElementSibling.onclick();
                        }
                    }
                }
            },
            func_update_translation = function(){
            //Cette fonction envoie la requête AJAX pour modifier la traduction
                var target = d.getElementById(this.getAttribute('data-target-item'));
                target.parentNode.classList.add('has-warning');
                target.parentNode.classList.remove('has-error');
                target.parentNode.classList.remove('has-success');
                target.setAttribute('disabled','disabled');

                if (target.value) {
                    //Envoi des données précises de l'expression et de sa traduction via AJAX
                    ajax_datas.data = {
                        "token": target.id.replace('translation_',''),
                        "translation": target.value
                    };
                    ajax_datas.success = function(msg) {
                        target.removeAttribute('disabled');
                        target.parentNode.classList.remove('has-warning');
                        target.parentNode.classList.remove('has-error');
                        target.parentNode.classList.remove('has-success');
                        if (msg.translated == true) {
                            // Ajoute une outline verte avec Bootstrap pour bien voir que la traduction a été faite
                            // Aucune incidence si bootstrap n'est pas présent
                            target.parentNode.classList.add('has-success');
                        } else {
                            target.parentNode.classList.add('has-error');
                        }

                        if ($().tooltip) {
                            // Twitter Bootstrap's tooltip
                            $(target).tooltip({
                                'placement':'left auto',
                                'container':'body',
                                'title':msg.message,
                                'trigger':'manual'
                            }).tooltip('show');
                            setTimeout(function(){$(target).tooltip('destroy');}, msg.translated == true ? 2000 : 5000);
                        }
                    };
                    if ($) {
                        // Si ajax possible via jQuery
                        $.ajax(ajax_datas);
                    } else {
                        // Sinon, erreur
                        console.error('jQuery is undefined.');
                        return false;
                    }
                }
            }/*,
            func_check_translations = function(){
                var target = d.getElementById(this.getAttribute('data-target-item')),
                    token = this.getAttribute('data-target-item').replace('translation_','');
                ajax_datas.data = {
                    'token': token,
                    'check_translations': true
                };
                ajax_datas.success = function(msg) {
                    target.removeAttribute('disabled');
                    target.parentNode.classList.remove('has-warning');
                    target.parentNode.classList.remove('has-error');
                    target.parentNode.classList.remove('has-success');
                    if (msg.list) {
                        //
                    } else {
                        //
                    }
                };
                $.ajax(ajax_datas);
            }*/;

        //Applique les fonctions à tous les éléments
        for (var i = 0; i < number_of_elements; i++) {
            if ($) {
                list_validators[i].onclick = func_update_translation;
            } else {
                list_validators[i].style.display = 'none';
            }
            list_copy_buttons[i].onclick = func_copy_initial_content
        }

    }
})(document, window.jQuery);