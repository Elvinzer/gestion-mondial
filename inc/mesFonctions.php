<?php
	//Fonction permettant de récupérer la range de poids max sur lequel se baser pour calculer le prix d'une expédition Mondial Relay
	function get_poids_max($poids_a_comparer){
		$monResultat = 0;
		global $wpdb ;
		$tarif_mondial_table_name = $wpdb->prefix . 'tarif_mondialrelay';
		
		// On fait la requête permettant de récupérer l'ensemble des poids trier dans l'ordre croissant
		$tousLesPoids = $wpdb->get_results("SELECT poids_max_gr FROM $tarif_mondial_table_name ORDER BY poids_max_gr");
		// On stock tous les résultats dans un tableau
		foreach ( $tousLesPoids as $poids ){
			$monPoidsMax[] = $poids->poids_max_gr;
		}
		
		for ($index_tarif = 0; $index_tarif < sizeof($monPoidsMax); $index_tarif++){
			if ( $poids_a_comparer == $monPoidsMax[$index_tarif]){
				$monResultat = $monPoidsMax[$index_tarif];
				return $monResultat;
				break;
			}
			
			if ( ($poids_a_comparer > $monPoidsMax[$index_tarif]) && ($poids_a_comparer < $monPoidsMax[$index_tarif+1]) ){
				$monResultat = $monPoidsMax[$index_tarif+1];
				return $monResultat;
				break;
			}
			
			if ($poids_a_comparer > $monPoidsMax[sizeof($monPoidsMax)-1]){
				echo 'Poids max autorisé par mondial Relay dépassé';
				break;
			}
		}
			
	}
	
	// On crée une fonction qui permettra d'envoyer le mail template de facturation d'expédition à la personne indiquée en paramètre
	function send_email($to, $order_id, $num_mondial_relay, $prix_ttc){
		//wp_mail($destinataire, $monSujet, $monMail);
		$subject = "Facture Byfrenchyz sur expédition de la commande " . $order_id;
		$tva = round($prix_ttc - $prix_ttc/1.2, 2);
		$prix_ht = $prix_ttc - $tva;
		
		$body = "<div>Bonjour,</div>
		<br><br>
		<div> Veuillez trouver ci-dessous le montant prélevé pour votre commande Mondial Relay N° $num_mondial_relay sur votre portefeuille conformément aux tarifs MondialRelay :<div>
		<br><br>
		<div> Commande Byfrenchyz n° $order_id </div>
		<br><br>
		<div> Prix HT : $prix_ht € </div><br>
		<div> TVA : $tva € </div><br>
		<div> Prix TTC : $prix_ttc € </div><br>
		<br><br><br>
		<div>Cordialement, </div>
		<br><br>
		<div>L'équipe Byfrenchyz.fr </div>";
		
		$headers = array('Content-Type: text/html; charset=UTF-8');

		wp_mail( $to, $subject, $body, $headers );
	}
	
	//Fonction permettant de récupérer le nom de la boutique vendeur stockée en base Byfrenchyz
	function get_vendor_name($vendor_id){
		global $wpdb;
		
		$nameVendeur = $wpdb->get_results("SELECT mp_usermeta.meta_value as 'Vendeur' 
		FROM `mp_usermeta` 
		WHERE user_id = " .$vendor_id. "
		AND mp_usermeta.meta_key = 'pv_shop_name'");
		
		
		foreach ( $nameVendeur as $go ) 
		{
			$nameVendeur = $go->Vendeur;
		}
		
		return $nameVendeur;
	}
	
	// Fonction qui permet d'envoyer un mail de facturation d'expédition au vendeur
	function send_email_facturation_expedition($to, $order_id, $num_mondial_relay, $prix_ttc, $nomBoutique, $laDate){
		//wp_mail($destinataire, $monSujet, $monMail);
		$order_id = $order_id;
		$subject = "Facture Byfrenchyz expédition commande " . $order_id;
		//TVA 20%
		$tva = round($prix_ttc - $prix_ttc/1.2, 2);
		$prix_ht = $prix_ttc - $tva;
		
		$body = "<div>Bonjour; <div> <br>
		<span class='preheader'>Veuillez trouver ci-dessous la facture correspondant au montant prélevé sur votre portefeuille Byfrenchyz conformément aux tarifs pratiqués par Mondial Relay.</span>
		<br><br><div>Commande Mondial Relay N° $num_mondial_relay</div>
    <table style = 'margin-top : 40px;' class='email-wrapper' width='100%' cellpadding='0' cellspacing='0' role='presentation'>
      <tr>
        <td align='center'>
          <table class='email-content' width='100%' cellpadding='0' cellspacing='0' role='presentation'>
		  
            <!-- Email Body -->
            <tr>
              <td class='email-body' width='100%' cellpadding='0' cellspacing='0'>
                <table class='email-body_inner' align='center' width='570' cellpadding='0' cellspacing='0' role='presentation'>
				
                  <!-- Body content -->
                  <tr>
                    <td class='content-cell'>
                      <div class='f-fallback'>
                        <h1 align : center>$nomBoutique</h1>
                        <table style = 'margin-top : 20px;'class='attributes' width='100%' cellpadding='0' cellspacing='0' role='presentation'>
                          <tr>
                            <td class='attributes_content'>
                              <table width='100%' cellpadding='0' cellspacing='0' role='presentation'>
                                <tr>
                                  <td class='attributes_item'>
                                    <span class='f-fallback'>
									  <strong>Montant prélevé sur votre portefeuille Byfrenchyz :</strong> $prix_ttc € TTC
									</span>
                                  </td>
                                </tr>
                              </table>
                            </td>
                          </tr>
                        </table>
                        <!-- Action -->
                        <table class='body-action' align='center' width='100%' cellpadding='0' cellspacing='0' role='presentation'>
                          <tr>

                        </table>
                        <table style = 'margin-top : 20px' class='purchase' width='100%' cellpadding='0' cellspacing='0'>
                          <tr>
                            <td>
                              <h3>Commande Byfrenchyz n° $order_id</h3>
                            </td>
                            <td>
                              <h3 class='align-right'>$laDate</h3>
                            </td>
                          </tr>
                          <tr>
                            <td colspan='2'>
                              <table class='purchase_content' width='100%' cellpadding='0' cellspacing='0'>
                                <tr>
                                  <th class='purchase_heading' align='left'>
                                    <p class='f-fallback'>Description</p>
                                  </th>
                                  <th class='purchase_heading' align='left'>
                                    <p class='f-fallback'>Montant</p>
                                  </th>
                                </tr>

                                <tr>
                                  <td width='80%' class='purchase_item'><span class='f-fallback'>Montant HT</span></td>
                                  <td class='align-right' width='20%' class='purchase_item'><span class='f-fallback'>$prix_ht €</span></td>
                                </tr>
                                <tr>
                                  <td width='80%' class='purchase_item'><span class='f-fallback'>TVA 20%</span></td>
                                  <td class='align-right' width='20%' class='purchase_item'><span class='f-fallback'>$tva €</span></td>
                                </tr>

                                <tr>
                                  <td width='80%' class='purchase_footer' valign='middle'>
                                    <p style='font-style : bold;' class='f-fallback purchase_total purchase_total--label'>Montant TTC</p>
                                  </td>
                                  <td width='20%' class='purchase_footer' valign='middle'>
                                    <p style='font-style : bold;' class='f-fallback purchase_total'>$prix_ttc €</p>
                                  </td>
                                </tr>
                              </table>
                            </td>
                          </tr>
                        </table>
       
                        <p>Cordialement,
                          <br><br><br>L'équipe Byfrenchyz</p>
                        <!-- Sub copy -->
                      </div>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr>
              <td>
                <table class='email-footer' align='center' width='570' cellpadding='0' cellspacing='0' role='presentation'>
                  <tr>
                    <td class='content-cell' align='center'>
                      <p class='f-fallback sub align-center'>&copy; Byfrenchyz 2019. Tout droits réservés.</p>
                      <p class='f-fallback sub align-center'>
                        www.byfrenchyz.fr
                      </p>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>";
		
		$headers = array('Content-Type: text/html; charset=UTF-8');

		wp_mail($to, $subject, $body, $headers );
	}		
	
	// Fonction qui permet d'envoyer un mail de notification de génération d'étiquette Mondial Relay au client et au vendeur
	function send_email_etiquette_mondial($numcommande, $numero_expedition, $lienPdf, $monMailClient, $monMailVendeur, $leprenomclient, $lenomclient, $lenomvendeur){
		///////////////////////////////////////////////////// Bloc d'envoi de mail client ///////////////////////////////////////////////
		$headers = array('Content-Type: text/html; charset=UTF-8');
		
		$SubjectMail = 'Numero de suivi Mondial Relay commande Byfrenchyz N° ' .$numcommande;
		
		$monBody = "<div>Bonjour $leprenomclient $lenomclient,</div><br><br>
		<div>Veuillez trouver ci-dessous votre numéro d'expédition Mondial Relay associé à votre commande Byfrenchyz N°$numcommande effectué ce jour : </div><br>
		<div>$numero_expedition</div><br>
		Pour suivre votre colis, veuillez vous rendre <a href=https://www.mondialrelay.fr/suivi-de-colis/>sur le site de Mondial Relay</a><br><br>
		<br><br>
		L'equipe Byfrenchyz";
		
		wp_mail($monMailClient, $SubjectMail, $monBody, $headers);
		
		///////////////////////////////////////////////////// Bloc d'envoi de mail vendeur ///////////////////////////////////////////////
		$SubjectMail = 'Etiquette Mondial Relay commande Byfrenchyz N° ' .$numcommande;
		$monBody = "<div>Bonjour $lenomvendeur,</div><br><br>
		<div>Veuillez trouver ci-dessous votre etiquette Mondial Relay associée à la commande Byfrenchyz N° $numcommande à coller sur le colis:</div><br>
		<div><a href=$lienPdf>Cliquez ici pour télécharger l'étiquette</a></div><br><br>
		<br>
		
		L'equipe Byfrenchyz";
		
		wp_mail($monMailVendeur, $SubjectMail, $monBody, $headers);
	}		
?>