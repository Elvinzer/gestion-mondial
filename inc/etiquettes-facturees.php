<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Titre de la page</title>
  <link rel="stylesheet" href="css/style.css">
</head>
	<body>
	<h1 class ="test"> Le tableau ci-dessous récupère les commandes dont les étiquettes Mondial Relay ont été facturées</h1>
		<?php
		require_once('mangoClass.php');
		require_once('mesFonctions.php');

		// On appelle la variable global de BDD $wpdb associé au site
		global $wpdb ;
		$myMangoPayAction = new Mangopay;
		$mondial_table_name = $wpdb->prefix . 'mondialrelay';
		$tarif_mondial_table_name = $wpdb->prefix . 'tarif_mondialrelay';

		// On crée et envoie la requete SQL
		$resultats = $wpdb->get_results("SELECT order_id as macommande, id_vendor as idvendeur,
							flag_generated as flag, num_commande_mr as numero_mr, weight_in_gr as poids
							FROM $mondial_table_name
							WHERE paid = 'yes'");

		/*************BLOC DAFFICHAGE DES DONNEES*************/
		// On affiche les résultats uniquement si il y a au moins un résultat à afficher
		if ($wpdb->num_rows != 0){
		?>
			<table border="1">
				<thead>
					<tr>
						<th>N° Commande</th>
						<th>Mail du vendeur</th>
						<th>Id vendeur</th>
						<th>Wallet vendeur Mangopay</th>
						<th>Numéro Mondial Relay</th>
						<th>Poids de la commande (gr)</th>
						<th>RangeMax poids MR retenu (gr)</th>
						<th>Montant TTC facturé au vendeur</th>
						<th>Lien vers Wallet vendeur</th>
					</tr>
				</thead>
			<tbody>
				<?php
					// pour chaque ligne (chaque enregistrement)
					foreach ( $resultats as $resultat )
					{
						$numcommande = $resultat->macommande;
						$monIdVendeur = $resultat->idvendeur;
						$mailVendeur = $myMangoPayAction->get_vendor_email($monIdVendeur);
						$monIdMangopay = $myMangoPayAction->get_user_id_mango($mailVendeur);
						$monWalletMangopay = $myMangoPayAction->get_wallet($monIdMangopay);
						$monNumeroMondialRelay = $resultat->numero_mr;
						$monPoids = $resultat->poids;
						$monPoidsMax = get_poids_max($monPoids);
						$lienWalletVendeur = 'https://dashboard.mangopay.com/User/'. $monIdMangopay .'/Wallets';

						$monMontant = $wpdb->get_results("SELECT tarif_ttc FROM $tarif_mondial_table_name
						WHERE poids_max_gr = " .$monPoidsMax."
						");
						foreach ( $monMontant as $go ){
							$monMontant = $go->tarif_ttc;
						}
					// DONNEES A AFFICHER dans chaque cellule de la ligne
				?>
					<tr id = 'contenu'>
						<td style = "text-align : center;"><?php echo $numcommande ?></td>
						<td style = "text-align : center;"><?php echo $mailVendeur ?></td>
						<td style = "text-align : center;"><?php echo $monIdVendeur ?></td>
						<td style = "text-align : center;"><?php echo $monWalletMangopay ?></td>
						<td style = "text-align : center;"><?php echo $monNumeroMondialRelay ?></td>
						<td style = "text-align : center;"><?php echo $monPoids ?></td>
						<td style = "text-align : center;"><?php echo $monPoidsMax ?></td>
						<td style = "text-align : center;"><?php echo $monMontant . ' €'; ?></td>
						<td style = "text-align : center;"><?php echo '<a target="_blank" href="'. $lienWalletVendeur .'">Lien</a>'; ?></td>
					</tr>
				<?php

					} // fin foreach
				?>
			</tbody>
			</table>
	<?php
	} else { ?>
		Aucune étiquette n'a encore été facturée
	<?php
	}
	?>
	</body>
</html>
