<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Titre de la page</title>
  <link rel="stylesheet" href="css/style.css">
</head>
	<body>
	<h1 class ="test"> Le tableau ci-dessous récupère les commandes pas encore générées (Enlever du filtre les commandes en "pending" une fois en prod </h1>
		<?php
		require_once('mesFonctions.php');

		// On appelle la variable global de BDD $wpdb associé au sitee
		global $wpdb ;

		$marue;
		$moncode;
		$maville;

		/********************** On crée la table SQL si elle n'existe pas *********************/
		$charset_collate = $wpdb->get_charset_collate();
		$mondial_table_name = $wpdb->prefix . 'mondialrelay';


		$commissions_sql = "CREATE TABLE IF NOT EXISTS $mondial_table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			order_id decimal(10,0) DEFAULT NULL,
			id_customer decimal(10,0) DEFAULT NULL,
			id_vendor decimal(10,0) DEFAULT NULL,
			id_item decimal(10,0) DEFAULT NULL,
			flag_generated varchar(45) DEFAULT NULL,
			time_generated datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			weight_in_gr decimal(10,0) DEFAULT NULL,
			contenu_colis varchar(45) DEFAULT NULL,
			paid varchar(20) DEFAULT NULL,
			amount_shipping decimal(10,2) DEFAULT NULL,
			time_paid datetime DEFAULT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		// On crée la table grace à Upgrade.php
		dbDelta($commissions_sql);

		/******************************** Fin de création de Table ***************************/

		// On crée et envoie la requete SQL
		$resultats = $wpdb->get_results("SELECT DISTINCT mp_wc_customer_lookup.first_name as 'Prenom', mp_wc_customer_lookup.last_name as 'Nom', mp_wc_order_stats.date_created as 'Date',mp_posts.post_author as 'IdVendeur', mp_usermeta.meta_value as 'Vendeur', mp_wc_order_stats.status as Statut, mp_woocommerce_order_items.order_id as NumeroCommande, mp_mondialrelay.flag_generated as Flag , mp_postmeta.meta_value as Point
		FROM ((mp_wc_order_product_lookup INNER JOIN mp_wc_order_stats ON mp_wc_order_stats.order_id= mp_wc_order_product_lookup.order_id),
			mp_wc_customer_lookup,
			mp_woocommerce_order_items,
			mp_posts,
			mp_usermeta)
		LEFT OUTER JOIN mp_mondialrelay ON mp_mondialrelay.order_id = mp_wc_order_stats.order_id AND
			mp_mondialrelay.id_vendor = mp_posts.post_author
		LEFT OUTER JOIN mp_postmeta ON mp_postmeta.post_id = mp_wc_order_stats.order_id AND mp_postmeta.meta_key = 'point_relais'
		WHERE
			mp_wc_order_stats.customer_id = mp_wc_customer_lookup.customer_id
			AND mp_woocommerce_order_items.order_id =  mp_wc_order_stats.order_id
			AND mp_woocommerce_order_items.order_item_type='line_item'
			AND mp_posts.post_title = mp_woocommerce_order_items.order_item_name
			AND mp_posts.post_author = mp_usermeta.user_id
			AND mp_usermeta.meta_key = 'pv_shop_name'
			AND mp_wc_order_stats.status <> 'wc-cancelled'
			AND mp_wc_order_stats.status <> 'wc-pending'
			AND mp_wc_order_stats.status <> 'wc-trash'
      AND mp_wc_order_stats.status <> 'wc-completed'
			AND mp_postmeta.meta_value <> 'unknown'
			AND mp_mondialrelay.flag_generated IS NULL
		ORDER BY Date DESC");

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
						<th>Adresse vendeur</th>
						<th>CP vendeur</th>
						<th>Ville vendeur</th>
						<th>Statut commande</th>
						<th>Point relais</th>
						<th>Poids (gr)</th>
						<th>Generation étiquette</th>
						<th>Généré ?</th>
					</tr>
				</thead>
			<tbody>
				<?php
					// pour chaque ligne (chaque enregistrement)
					foreach ( $resultats as $resultat )
					{
						$numcommande = $resultat->NumeroCommande;
						$idDuVendeur = $resultat->IdVendeur;

						// Avant d'afficher les commandes à Générer, on check qu'elle n'est pas encore été généré
						if ($resultat->Flag <> 'yes'){
							// On initilise le numéro de point relais à "NULL", on ne sait pas encore si un point a été choisi
							$monpoint = '';
							// On crée et envoie la requete SQL pour récupérer la rue du vendeur
							$adressesvendeur = $wpdb->get_results("SELECT DISTINCT mp_wc_order_stats.date_created as 'Date', mp_woocommerce_order_items.order_item_name as Produit, mp_usermeta.meta_value as Rue, mp_wc_order_stats.status as Statut, mp_woocommerce_order_items.order_id as NumeroCommande, mp_wc_order_stats.customer_id as IdClient, mp_usermeta.user_id as IdVendeur
								FROM mp_wc_order_product_lookup, mp_wc_order_stats, mp_wc_customer_lookup, mp_woocommerce_order_items, mp_posts, mp_usermeta
								WHERE mp_wc_order_stats.order_id= mp_wc_order_product_lookup.order_id
								AND mp_wc_order_stats.customer_id = mp_wc_customer_lookup.customer_id
								AND mp_woocommerce_order_items.order_id =  mp_wc_order_stats.order_id
								AND mp_woocommerce_order_items.order_item_type='line_item'
								AND mp_posts.post_title = mp_woocommerce_order_items.order_item_name
								AND mp_posts.post_author = mp_usermeta.user_id
								AND mp_usermeta.meta_key = '_wcv_store_address1'
                                AND mp_woocommerce_order_items.order_id = " .$numcommande."
								AND mp_wc_order_stats.status <> 'wc-cancelled'
								AND mp_usermeta.user_id = " .$idDuVendeur. "
								");
							foreach ( $adressesvendeur as $go )
							{
								$marue = $go->Rue;
							}

							// On récupère le code postal du vendeur
							$codepostalvendeur = $wpdb->get_results("SELECT DISTINCT mp_wc_order_stats.date_created as 'Date', mp_woocommerce_order_items.order_item_name as Produit, mp_usermeta.meta_value as Codepostal, mp_wc_order_stats.status as Statut, mp_woocommerce_order_items.order_id as NumeroCommande, mp_wc_order_stats.customer_id as IdClient, mp_usermeta.user_id as IdVendeur
								FROM mp_wc_order_product_lookup, mp_wc_order_stats, mp_wc_customer_lookup, mp_woocommerce_order_items, mp_posts, mp_usermeta
								WHERE mp_wc_order_stats.order_id= mp_wc_order_product_lookup.order_id
									AND mp_wc_order_stats.customer_id = mp_wc_customer_lookup.customer_id
									AND mp_woocommerce_order_items.order_id =  mp_wc_order_stats.order_id
									AND mp_woocommerce_order_items.order_item_type='line_item'
									AND mp_posts.post_title = mp_woocommerce_order_items.order_item_name
									AND mp_posts.post_author = mp_usermeta.user_id
									AND mp_usermeta.meta_key = '_wcv_store_postcode'
	                                AND mp_woocommerce_order_items.order_id = " .$numcommande."
									AND mp_usermeta.user_id = " .$idDuVendeur. "
									AND mp_wc_order_stats.status <> 'wc-cancelled'");
							foreach ( $codepostalvendeur as $go )
							{
								$moncode = $go->Codepostal;
							}

							//Requete pour récupérer la ville du vendeur
							$villevendeur = $wpdb->get_results("SELECT DISTINCT mp_wc_order_stats.date_created as 'Date', mp_woocommerce_order_items.order_item_name as Produit, mp_usermeta.meta_value as Ville, mp_wc_order_stats.status as Statut, mp_woocommerce_order_items.order_id as NumeroCommande, mp_wc_order_stats.customer_id as IdClient, mp_usermeta.user_id as IdVendeur
								FROM mp_wc_order_product_lookup, mp_wc_order_stats, mp_wc_customer_lookup, mp_woocommerce_order_items, mp_posts, mp_usermeta
								WHERE mp_wc_order_stats.order_id= mp_wc_order_product_lookup.order_id
									AND mp_wc_order_stats.customer_id = mp_wc_customer_lookup.customer_id
									AND mp_woocommerce_order_items.order_id =  mp_wc_order_stats.order_id
									AND mp_woocommerce_order_items.order_item_type='line_item'
									AND mp_posts.post_title = mp_woocommerce_order_items.order_item_name
									AND mp_posts.post_author = mp_usermeta.user_id
									AND mp_usermeta.meta_key = '_wcv_store_city'
	                                AND mp_woocommerce_order_items.order_id = " .$numcommande."
									AND mp_usermeta.user_id = " .$idDuVendeur. "
									AND mp_wc_order_stats.status <> 'wc-cancelled'");
							foreach ( $villevendeur as $go )
							{
								$maville = $go->Ville;
							}


							//Requete pour récupérer le point relais selectionné par le client
							$pointrelais = $wpdb->get_results("SELECT mp_postmeta.meta_value as Point
								from mp_postmeta
								WHERE mp_postmeta.post_id = " .$numcommande."
								AND mp_postmeta.meta_key = 'point_relais'");
							if ($wpdb->num_rows != 0){
								foreach ( $pointrelais as $point )
								{
									$monpoint = $point->Point;
								}
							}

							//Requete pour récupérer la liste des produits commandés (pas le nombre)
							$listeproduit = $wpdb->get_results("SELECT DISTINCT mp_wc_order_stats.date_created as 'Date', mp_woocommerce_order_items.order_item_name as Produit, GROUP_CONCAT(DISTINCT mp_woocommerce_order_items.order_item_name ) as Contenu_colis, mp_wc_order_stats.status as Statut, mp_woocommerce_order_items.order_id as NumeroCommande, mp_wc_order_stats.customer_id as IdClient, mp_usermeta.user_id as IdVendeur
								FROM mp_wc_order_product_lookup, mp_wc_order_stats, mp_wc_customer_lookup, mp_woocommerce_order_items, mp_posts, mp_usermeta
								WHERE mp_wc_order_stats.order_id= mp_wc_order_product_lookup.order_id
								AND mp_wc_order_stats.customer_id = mp_wc_customer_lookup.customer_id
								AND mp_woocommerce_order_items.order_id =  mp_wc_order_stats.order_id
								AND mp_woocommerce_order_items.order_item_type='line_item'
								AND mp_posts.post_title = mp_woocommerce_order_items.order_item_name
								AND mp_posts.post_author = mp_usermeta.user_id
								AND mp_usermeta.meta_key = '_wcv_store_address1'
                                AND mp_woocommerce_order_items.order_id = " .$numcommande."
								AND mp_usermeta.user_id = " .$idDuVendeur. "
								AND mp_wc_order_stats.status <> 'wc-cancelled'");
							if ($wpdb->num_rows != 0){
								foreach ( $listeproduit as $liste )
								{
									$maListeProduit = $liste->Contenu_colis;
								}
							}


							//Requete pour récupérer le poids de la commande associée
							$calculpoids = $wpdb->get_results("SELECT DISTINCT mp_wc_order_stats.date_created as 'Date', mp_woocommerce_order_items.order_item_name as Produit, mp_usermeta.meta_value as Rue, mp_wc_order_stats.status as Statut, mp_woocommerce_order_items.order_id as NumeroCommande, mp_wc_order_stats.customer_id as IdClient, mp_usermeta.user_id as IdVendeur, mp_wc_order_product_lookup.product_qty as Quantity, mp_postmeta.meta_value as Poids, mp_wc_order_product_lookup.product_qty * mp_postmeta.meta_value * 1000 as Poids_Total
								FROM mp_wc_order_product_lookup, mp_wc_order_stats, mp_wc_customer_lookup, mp_woocommerce_order_items, mp_posts, mp_usermeta, mp_postmeta
								WHERE mp_wc_order_stats.order_id= mp_wc_order_product_lookup.order_id
								AND mp_wc_order_stats.customer_id = mp_wc_customer_lookup.customer_id
								AND mp_woocommerce_order_items.order_id =  mp_wc_order_stats.order_id
								AND mp_woocommerce_order_items.order_item_type='line_item'
								AND mp_posts.post_title = mp_woocommerce_order_items.order_item_name
								AND mp_posts.post_author = mp_usermeta.user_id
								AND mp_usermeta.meta_key = '_wcv_store_address1'
                                AND mp_woocommerce_order_items.order_id = " .$numcommande."
								AND mp_usermeta.user_id = " .$idDuVendeur. "
                                AND mp_woocommerce_order_items.order_item_id = mp_wc_order_product_lookup.order_item_id
                                AND mp_wc_order_product_lookup.product_id = mp_postmeta.post_id
                                AND mp_postmeta.meta_key ='_weight'
								AND mp_wc_order_stats.status <> 'wc-cancelled'");
							if ($wpdb->num_rows != 0){
								$monpoids =0;
								foreach ( $calculpoids as $poids )
								{
									$monpoids = $monpoids + $poids->Poids_Total;
								}
							}

					// DONNEES A AFFICHER dans chaque cellule de la ligne
				?>
							<tr id = 'contenu'>
								<td style = "text-align : center;"><?php echo $resultat->NumeroCommande; ?></td>
								<td><?php echo $resultat->Prenom; ?></td>
								<td><?php echo $resultat->Nom; ?></td>
								<td style = "text-align : center;" id=<?= 'liste'.$resultat->IdVendeur . $resultat->NumeroCommande?>><?php echo $maListeProduit; ?></td>
								<td><?php echo date('d-m-Y H:i:s', strtotime($resultat->Date)); ?></td>
								<td><?php echo $resultat->Vendeur; ?></td>
								<td><?php echo $marue; ?></td>
								<td style = "text-align : center;"><?php echo $moncode; ?></td>
								<td style = "text-align : center;"><?php echo $maville; ?></td>
								<td style = "text-align : center;"><?php echo $resultat->Statut; ?></td>
								<td><?php echo $monpoint; ?></td>
								<td style = "text-align : center;" id=<?= 'weight'.$resultat->IdVendeur . $resultat->NumeroCommande?>><?php echo $monpoids; ?></td>
								<td style = "text-align : center;"><input type="submit" name =<?=$resultat->IdVendeur . $resultat->NumeroCommande?> class=<?=$resultat->IdVendeur?> id=<?=$resultat->NumeroCommande?> value="Generer etiquette" /></td>
								<td style = "text-align : center;" id=<?= 'generation'.$resultat->IdVendeur . $resultat->NumeroCommande?> > <?php echo 'non'; ?></td>
							</tr>
				<?php
						}
					} // fin foreach
				?>
			</tbody>
			</table>

	<?php
	} else { ?>
		Pas de ventes à afficher
	<?php
	}
	?>
		<script src="../wp-content/plugins/gestion-mondial/js/script.js" type="text/javascript">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
	</body>
</html>
