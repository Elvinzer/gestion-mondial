jQuery.noConflict();
jQuery(document).ready(function(){
	alert("Vous pouvez à présent générer les étiquettes !");

	// On veut récupérer l'id et la class d'un input cliqué / L'id étant le numéro de commande et la class l'id du vendeur correspondant
	jQuery('td > input').click(function(){
		order_id = jQuery(this).attr('id');
		vendor_id = jQuery(this).attr('class');
		
		monid = order_id;
		monvendeurid = vendor_id;
		
		
		//var elem = document.getElementById(monid);
		//elem.parentNode.removeChild(elem);
		var mon_id_str = monid + "";
		var mon_vendeur_id_str = monvendeurid + "";
		
		var vendeur_commande = mon_vendeur_id_str + mon_id_str;
		//On supprime le bouton pour éviter les problemes de double clique
		document.getElementsByName(vendeur_commande).forEach(el => el.remove());
		
		// On récupère le poids de la commande indiqué dans le tableau
		order_weight = document.getElementById('weight'+vendeur_commande).innerHTML;
		
		// On récupère la liste des articles présents dans le colis (sans le nombre)
		order_list = document.getElementById('liste'+vendeur_commande).innerHTML;
		
		//alert(order_id);
		// On change le contenu dans le champs de la commande associé au clique
		document.getElementById('generation'+vendeur_commande).innerHTML = 'oui';
		
		// On renvoie la valeur en appelant la fonction wp_ajax_ajout_ligne présente dans le functions.php du theme
		jQuery.post(
			ajaxurl,
			{
				'action': 'ajout_ligne',
				'postnumcommande' : monid,
				'postvendorid' : monvendeurid,
				'postweight' : order_weight,
				'postlist' : order_list
			},
			function(response){
					console.log(response);
					//window.location.reload();
				}
		);
	});
});