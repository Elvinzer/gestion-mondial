jQuery.noConflict();
jQuery(document).ready(function(){
	alert("Vous pouvez à présent générer la facturation !");

	// On veut récupérer l'id et la class d'un input cliqué / L'id étant le numéro de commande et la class l'id du vendeur correspondant
	jQuery('td > input').click(function(){
		
		vendor_mangopay_id = jQuery(this).attr('class');
		vendor_wallet_id = jQuery(this).attr('id');

		// On récupère la valeur à facturer en enlevant le . pour respecter les parametres à rentrer pour l'API Mangopay
		amount_for_base = jQuery(this).attr('name');
		amount_to_transfer = jQuery(this).attr('name').replace('.', '');
		currow = jQuery(this).closest('tr');
        order_id = currow.find('td:eq(0)').text();
		vendor_id = currow.find('td:eq(1)').text();
		vendor_mail = currow.find('td:eq(2)').text();
		numero_mondial = currow.find('td:eq(5)').text();
		
		jQuery(this).remove();
		
		// On renvoie la valeur en appelant la fonction wp_ajax_generation_facturation présente dans le functions.php du theme
		jQuery.post(
			ajaxurl,
			{
				'action': 'generation_facturation',
				'idVendorMangoPay' : vendor_mangopay_id,
				'numWalletVendorMangoPay' : vendor_wallet_id,
				'the_amount_to_transfer' : amount_to_transfer,
				'idVendorBase' : vendor_id,
				'orderId' : order_id,
				'amountForBase' :amount_for_base,
				'vendor_email' : vendor_mail,
				'numero_mr' : numero_mondial
			},
			function(response){
					console.log(response);
				}
		);
		
	});
});