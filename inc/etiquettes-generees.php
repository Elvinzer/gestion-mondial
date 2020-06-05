<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Titre de la page</title>
  <link rel="stylesheet" href="css/style.css">
</head>
	<body>
	<h1> Le tableau ci-dessous récupère les commandes dont les étiquettes ont été générées</h1>
		<?php
		// On appelle la variable global de BDD $wpdb associé au site 
		global $wpdb ;
		require_once('mesFonctions.php');
		$mondial_table_name = $wpdb->prefix . 'mondialrelay';
		
		// On crée et envoie la requete SQL
		$resultats = $wpdb->get_results("SELECT DISTINCT mp_wc_customer_lookup.first_name as 'Prenom', mp_wc_customer_lookup.last_name as 'Nom', mp_wc_order_stats.date_created as 'Date', mp_mondialrelay.contenu_colis as ListeProduit, mp_posts.post_author as 'IdVendeur', mp_usermeta.meta_value as 'Vendeur', mp_wc_order_stats.status as Statut, mp_woocommerce_order_items.order_id as NumeroCommande, mp_mondialrelay.point_relais as PointRelais, mp_mondialrelay.num_commande_mr as NumeroMondial, mp_mondialrelay.lien_etiquette as Lien, mp_mondialrelay.time_generated as 'DateGeneration'
			FROM ((mp_wc_order_product_lookup INNER JOIN mp_wc_order_stats ON mp_wc_order_stats.order_id= mp_wc_order_product_lookup.order_id),
				mp_wc_customer_lookup, 
				mp_woocommerce_order_items, 
				mp_posts, 
				mp_usermeta)
				LEFT OUTER JOIN mp_mondialrelay ON mp_mondialrelay.order_id = mp_wc_order_stats.order_id
			WHERE  
				mp_wc_order_stats.customer_id = mp_wc_customer_lookup.customer_id 
				AND mp_woocommerce_order_items.order_id =  mp_wc_order_stats.order_id
				AND mp_woocommerce_order_items.order_item_type='line_item'
				AND mp_posts.post_title = mp_woocommerce_order_items.order_item_name
				AND mp_posts.post_author = mp_usermeta.user_id
				AND mp_usermeta.meta_key = 'pv_shop_name'
				AND mp_wc_order_stats.status <> 'wc-cancelled'
				AND mp_wc_order_stats.status <> 'wc-pending'
				AND mp_mondialrelay.num_commande_mr <> 'null'
			GROUP BY mp_mondialrelay.num_commande_mr DESC");
		
		/*************BLOC DAFFICHAGE DES DONNEES*************/
		// On affiche les résultats uniquement si il y a au moins un résultat à afficher
		if ($wpdb->num_rows != 0){
		?>
			<table border="1">
				<thead>
					<tr>
						<th>N° Commande</th>
						<th>Prénom Client</th>
						<th>Nom Client</th>
						<th>Liste Produits</th>
						<th>Date d'achat</th>
						<th>Vendeur</th>
						<th>Point relais</th>
						<th>Numéro mondial relay</th>
						<th>Lien étiquette</th>
						<th>Date de génération de l'étiquette</th>
					</tr>
				</thead>
			<tbody>
				<?php
					// pour chaque ligne (chaque enregistrement)
					foreach ( $resultats as $resultat ) 
					{
						$numcommande = $resultat->NumeroCommande;
						$numMondialRelay = $resultat->NumeroMondial;
						
						$idVendeur = $wpdb->get_results("SELECT id_vendor FROM $mondial_table_name WHERE num_commande_mr = " .$numMondialRelay."
						");
						foreach ( $idVendeur as $monId ) 
						{
							$idVendeur = $monId->id_vendor;
						}
						$maBoutique = get_vendor_name($idVendeur);
							
					// DONNEES A AFFICHER dans chaque cellule de la ligne
				?>
					<tr id = 'contenu'>
						<td style = "text-align : center;"><?php echo $numcommande; ?></td>
						<td><?php echo $resultat->Prenom; ?></td>
						<td><?php echo $resultat->Nom; ?></td>
						<td><?php echo $resultat->ListeProduit; ?></td>
						<td><?php echo date('d-m-Y H:i:s', strtotime($resultat->Date)); ?></td>
						<td><?php echo $maBoutique; ?></td>
						<td><?php echo $resultat->PointRelais; ?></td>
						<td style = "text-align : center;"><?php echo $numMondialRelay; ?></td>
						<td style = "text-align : center;"><?php echo '<a href="'.$resultat->Lien.'">Lien</a>'; ?></td>
						<td style = "text-align : center;"><?php echo date('d-m-Y H:i:s', strtotime($resultat->DateGeneration)); ?></td>
					</tr>
				<?php
						
					} // fin foreach
				?>
			</tbody>
			</table>
	<?php
	} else { ?>
		Aucune étiquette n'a encore été généré
	<?php
	}
	?>
	</body>
</html>