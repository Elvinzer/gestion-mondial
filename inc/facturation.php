<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Page de facturation</title>
  <link rel="stylesheet" href="css/style.css">
</head>
	<body>
	<h1> Cette page affiche les commandes dont les facturations d'expédition n'ont pas encore été faites </h1>
	<h2> Les facturations s'effectuent uniquement sur des commandes notifiées comme "Terminé" par le back-office du site </h2>
	<h2 style ='font-style : italic;'> (En statut "pending", les fonds seraient encore dans le wallet du client, donc pas dispo pour la facturation) </h2>

<?php
	require_once('mangoClass.php');
	require_once('mesFonctions.php');
	global $wpdb ;
	$pre_url = 'https://api.sandbox.mangopay.com/v2.01/';
	

	
	/********************** On crée la table SQL si elle n'existe pas *********************/
		$charset_collate = $wpdb->get_charset_collate();
		$tarif_mondial_table_name = $wpdb->prefix . 'tarif_mondialrelay';
		$prefix_base = $wpdb->prefix;
		$mondial_table_name = $wpdb->prefix . 'mondialrelay';

		$creation_tarifs_sql = "CREATE TABLE IF NOT EXISTS $tarif_mondial_table_name (
			id int(10) NOT NULL AUTO_INCREMENT,
			poids_max_gr decimal(10,0) DEFAULT NULL,
			tarif_ttc decimal(10,2) DEFAULT NULL,
			pays varchar(45) DEFAULT NULL,
			annee decimal(10,0) DEFAULT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		// On crée la table grace à Upgrade.php
		dbDelta($creation_tarifs_sql);

		/******************************** Fin de création de Table ***************************/


	// On crée un nouvel objet de la classe Mangopay (mangoClass.php)
	$myMangoPayAction = new Mangopay;
	
	
	$commandes = $wpdb->get_results("SELECT $mondial_table_name.order_id as macommande, $mondial_table_name.id_vendor as idvendeur, 
							$mondial_table_name.flag_generated as flag, 
							$mondial_table_name.num_commande_mr as numero_mr, $mondial_table_name.weight_in_gr as poids, ".$prefix_base."wc_order_stats.status
							FROM $mondial_table_name, ".$prefix_base."wc_order_stats
							WHERE $mondial_table_name.paid IS NULL
							AND $mondial_table_name.order_id = ".$prefix_base."wc_order_stats.order_id
							AND ".$prefix_base."wc_order_stats.status = 'wc-completed'
							");
		
		/*************BLOC DAFFICHAGE DES DONNEES*************/
		// On affiche les résultats uniquement si il y a au moins un résultat à afficher
		if ($wpdb->num_rows != 0){
		?>
			<table border="1">
				<thead>
					<tr>
						<th>N° Commande</th>
						<th>Id Vendeur en base</th>
						<th>Mail du vendeur en base</th>
						<th>Id vendeur Mangopay</th>
						<th>Wallet vendeur Mangopay</th>
						<th>Numéro Mondial Relay</th>
						<th>Poids de la commande (gr)</th>
						<th>RangeMax poids MR retenu (gr)</th>
						<th>Montant TTC à facturer au vendeur</th>
						<th>Generation de la facturation</th>
						<th>Lien vers Wallet vendeur</th>
					</tr>
				</thead>
			<tbody>
				<?php
					// pour chaque ligne (chaque enregistrement)
					foreach ( $commandes as $commande )
					{
						// Avant d'afficher les commandes à facturer, on check que chacune ne soit pas encore facturé
						if ($commande->flag = 'yes'){
							
							$monNumeroDeCommande = $commande->macommande;
							$monIdVendeur = $commande->idvendeur;
							$monNumeroMondialRelay = $commande->numero_mr;
							$monPoids = $commande->poids;
							$mailVendeur = $myMangoPayAction->get_vendor_email($monIdVendeur);
							$monIdMangopay = $myMangoPayAction->get_user_id_mango($mailVendeur);
							$monWalletMangopay = $myMangoPayAction->get_wallet($monIdMangopay);
							$monPoidsMax = get_poids_max($monPoids);
							$lienWalletVendeur = 'https://dashboard.sandbox.mangopay.com/User/'. $monIdMangopay .'/Wallets';
								
							$monMontant = $wpdb->get_results("SELECT tarif_ttc FROM $tarif_mondial_table_name
							WHERE poids_max_gr = " .$monPoidsMax."
							");
							foreach ( $monMontant as $go ){
								$monMontant = $go->tarif_ttc;
							}
		
					//var_dump $monPoidsMax;
							
					// DONNEES A AFFICHER dans chaque cellule de la ligne
				?>
							<tr id = 'contenu'>
								<td style = "text-align : center;" id=<?=$monIdVendeur.$monNumeroDeCommande?>><?php echo $monNumeroDeCommande; ?></td>
								<td style = "text-align : center;"><?php echo $monIdVendeur ?></td>
								<td style = "text-align : center;"><?php echo $mailVendeur ?></td>
								<td style = "text-align : center;"><?php echo $monIdMangopay ?></td>
								<td style = "text-align : center;"><?php echo $monWalletMangopay ?></td>
								<td style = "text-align : center;"><?php echo $monNumeroMondialRelay; ?></td>
								<td style = "text-align : center;"><?php echo $monPoids; ?></td>
								<td style = "text-align : center;"><?php echo $monPoidsMax; ?></td>
								<td style = "text-align : center;"><?php echo $monMontant . ' €'; ?></td>
								<td style = "text-align : center;"><input type="submit" name=<?=$monMontant?>  class=<?=$monIdMangopay?> id=<?=$monWalletMangopay?> value="Generer la facturation" /></td>
								<td style = "text-align : center;"><?php echo '<a target="_blank" href="'. $lienWalletVendeur .'">Lien</a>'; ?></td>
							</tr>
				<?php
						}
					} // fin foreach
				?>
			</tbody>
			</table>
		<?php
		} else { ?>
			Pas de facturation(s) à effectuer
			<?php
			}
		?>
		<script src="../wp-content/plugins/gestion-mondial/js/script_facturation.js" type="text/javascript">
	</body>
</html>