<?php

/*
Plugin Name: Gestion Mondial
Description: Plugin Wordpress gestion etiquette Mondial Relay
Version: 1.0.0
Author: Cedric BERNARD
Author URI: https://www.linkedin.com/in/cedricbernard06/
*/
require_once('inc/mangoClass.php');
require_once('inc/mesFonctions.php');
require_once('inc/mondialCart.php');

$num_expedition_mr ='';
$lien_pdf_mr='';

//.manage-column.column-gmw_address

// Quand la commande passe en termine, on envoie un mail de facturation au client
function send_billing_commission_mail($order_id) {
	global $wpdb ;
	$commissionToBill = 0;
	$mailVendeur = '';

	$mesresultats = $wpdb->get_results("SELECT mp_pv_commission.order_id , 
	SUM(mp_pv_commission.total_due) as 'vendor_commission',
	SUM(mp_pv_commission.tax) as 'total_tva',
	ROUND(SUM(mp_wc_order_stats.net_total) - SUM(mp_pv_commission.total_due),2) as 'byfrenchyz_commission',
	mp_users.user_email as 'MailVendeur'
	FROM `mp_pv_commission`, `mp_wc_order_stats`, `mp_users`
	WHERE mp_wc_order_stats.order_id = mp_pv_commission.order_id 
	AND mp_users.ID = mp_pv_commission.vendor_id
	AND mp_pv_commission.order_id = ".$order_id."");

	if ($wpdb->num_rows != 0){
		// On parcourt toutes les lignes de résultats pour récupérer les datas nécessaires
			foreach ( $mesresultats as $resultat )
		{
				$commissionToBill = $resultat->byfrenchyz_commission;
				$mailVendeur =  $resultat->MailVendeur;
		}
	}
	// Envoie du tout par mail
	send_email_facturation_commission($mailVendeur, $order_id, $commissionToBill);
}
add_action( 'woocommerce_order_status_completed', 'send_billing_commission_mail' );

add_action( 'admin_menu', 'add_menu_plugin' );


function add_menu_plugin() {
  add_menu_page( 'Gestion Mondial Relay', 'Gestion Mondial Relay', 'read', 'Gestion-Mondial-Relay_Dashboard', 'MondialRelay_commandes' );
  add_submenu_page( 'Gestion-Mondial-Relay_Dashboard', 'Gestion Mondial Relay', 'Commandes', 'read', 'Gestion-Mondial-Relay_Dashboard', 'MondialRelay_commandes');
  add_submenu_page( 'Gestion-Mondial-Relay_Dashboard', 'Gestion Mondial Relay', 'Etiquettes générées', 'read', 'MondialRelay_EtiquettesGenerees', 'MondialRelay_EtiquettesGenerees');
	add_submenu_page( 'Gestion-Mondial-Relay_Dashboard', 'Gestion Mondial Relay', 'Facturation', 'read', 'MondialRelay_facturation', 'MondialRelay_facturation');
	add_submenu_page( 'Gestion-Mondial-Relay_Dashboard', 'Gestion Mondial Relay', 'Etiquettes Facturées', 'read', 'MondialRelay_facturees', 'MondialRelay_facturees');
}

function MondialRelay_commandes() {
    include('inc/commandes.php');
}

function MondialRelay_facturation(){
    include('inc/facturation.php');
}

function MondialRelay_EtiquettesGenerees(){
	include('inc/etiquettes-generees.php');
}

function MondialRelay_facturees(){
	include('inc/etiquettes-facturees.php');
}

//Fonction permettant d'ajouter une feuille de style
function add_plugin_css() {
  $plugin_url = plugin_dir_url( __FILE__ );
  // Le time() permet de créer un versioning du css plus souvent (uniquement utile dans la phase de dev afin de debug)
  wp_enqueue_style( 'style-gestion-mondial',  $plugin_url . "css/style-gestion-mondial.css" , array(), time());
}
add_action( 'admin_enqueue_scripts', 'add_plugin_css' );

// On ajoute les add actions concernant le plugin Mondial Relay
//add_action('wp_enqueue_scripts', 'add_js_scripts');
add_action('wp_ajax_ajout_ligne', 'ajout_ligne_mondialrelay');
add_action('wp_ajax_creation_etiquette', 'creation_etiquette_mondialrelay');
add_action('wp_ajax_generation_facturation', 'generation_facturation');


// ajout_ligne_mondialrelay() est la fonction permettant d'ajouter une ligne dans la table mondialrelay
function ajout_ligne_mondialrelay() {
	// On déclare la var global de la BDD wordpress
	global $wpdb ;

	// On récupère le préfix et on y ajoute le nom de table 'mondialrelay'
	$mondial_table_name = $wpdb->prefix . 'mondialrelay';

	// On récupère le numéro de la commande concernée via l'id du bouton cliqué
	$mon_numero_commande = $_POST['postnumcommande'];
	$mon_vendeur_commande = $_POST['postvendorid'];
	$mon_poids_commande = $_POST['postweight'];
	$ContenuColis = $_POST['postlist'];
	// On met le flag manuellement à 'Yes' pour dire que l'étiquette Mondial Relay a été généré (car cliqué)
	$flag = 'yes';

	$mesresultats = $wpdb->get_results("SELECT DISTINCT mp_wc_customer_lookup.first_name as 'Prenom', mp_wc_customer_lookup.last_name as 'Nom', mp_wc_order_stats.date_created as 'Date', mp_woocommerce_order_items.order_item_name as Produit, mp_usermeta.meta_value as 'Vendeur', mp_wc_order_stats.status as Statut, mp_woocommerce_order_items.order_id as NumeroCommande, mp_wc_order_stats.customer_id as IdClient, mp_usermeta.user_id as IdVendeur, mp_postmeta.meta_value as Point
	FROM mp_wc_order_product_lookup, mp_wc_order_stats, mp_wc_customer_lookup, mp_woocommerce_order_items, mp_posts, mp_usermeta, mp_postmeta
	WHERE mp_wc_order_stats.order_id= mp_wc_order_product_lookup.order_id
		AND mp_wc_order_stats.customer_id = mp_wc_customer_lookup.customer_id
		AND mp_woocommerce_order_items.order_id =  mp_wc_order_stats.order_id
		AND mp_woocommerce_order_items.order_item_type='line_item'
		AND mp_posts.post_title = mp_woocommerce_order_items.order_item_name
		AND mp_posts.post_author = mp_usermeta.user_id
		AND mp_usermeta.meta_key = 'pv_shop_name'
		AND mp_wc_order_stats.status <> 'wc-cancelled'
    AND mp_wc_order_stats.status <> 'wc-completed'
		AND mp_woocommerce_order_items.order_id = " .$mon_numero_commande. "
		AND mp_postmeta.post_id = " .$mon_numero_commande. "
		AND mp_usermeta.user_id = " .$mon_vendeur_commande. "
		AND mp_postmeta.meta_key = 'point_relais'
	ORDER BY Date DESC");
	var_dump($mesresultats);
	if ($wpdb->num_rows != 0){
		// On parcourt toutes les lignes de résultats pour récupérer les datas nécessaires
			foreach ( $mesresultats as $resultat )
		{
				$idclient = $resultat->IdClient;
				$idvendeur = $resultat->IdVendeur;
				$prenomclient = $resultat->Prenom;
				$nomclient = $resultat->Nom;
				$pointrelais = $resultat->Point;
				$nomvendeur = $resultat->Vendeur;
		}

	// On récupère la rue du vendeur
	$adressesvendeur = $wpdb->get_results("SELECT DISTINCT mp_wc_order_stats.date_created as 'Date', mp_woocommerce_order_items.order_item_name as Produit, mp_usermeta.meta_value as Rue, mp_wc_order_stats.status as Statut, mp_woocommerce_order_items.order_id as NumeroCommande, mp_wc_order_stats.customer_id as IdClient, mp_usermeta.user_id as IdVendeur
		FROM mp_wc_order_product_lookup, mp_wc_order_stats, mp_wc_customer_lookup, mp_woocommerce_order_items, mp_posts, mp_usermeta
		WHERE mp_wc_order_stats.order_id= mp_wc_order_product_lookup.order_id
			AND mp_wc_order_stats.customer_id = mp_wc_customer_lookup.customer_id
			AND mp_woocommerce_order_items.order_id =  mp_wc_order_stats.order_id
			AND mp_woocommerce_order_items.order_item_type='line_item'
			AND mp_posts.post_title = mp_woocommerce_order_items.order_item_name
			AND mp_posts.post_author = mp_usermeta.user_id
			AND mp_usermeta.meta_key = '_wcv_store_address1'
	        AND mp_woocommerce_order_items.order_id = " .$mon_numero_commande. "
			AND mp_usermeta.user_id = " .$mon_vendeur_commande. "
			AND mp_wc_order_stats.status <> 'wc-cancelled'");
	foreach ( $adressesvendeur as $go )
	{
		$marue = $go->Rue;
	}

	// On récupère le Code postal du vendeur
	$codepostalvendeur = $wpdb->get_results("SELECT DISTINCT mp_wc_order_stats.date_created as 'Date', mp_woocommerce_order_items.order_item_name as Produit, mp_usermeta.meta_value as Codepostal, mp_wc_order_stats.status as Statut, mp_woocommerce_order_items.order_id as NumeroCommande, mp_wc_order_stats.customer_id as IdClient, mp_usermeta.user_id as IdVendeur
		FROM mp_wc_order_product_lookup, mp_wc_order_stats, mp_wc_customer_lookup, mp_woocommerce_order_items, mp_posts, mp_usermeta
		WHERE mp_wc_order_stats.order_id= mp_wc_order_product_lookup.order_id
			AND mp_wc_order_stats.customer_id = mp_wc_customer_lookup.customer_id
			AND mp_woocommerce_order_items.order_id =  mp_wc_order_stats.order_id
			AND mp_woocommerce_order_items.order_item_type='line_item'
			AND mp_posts.post_title = mp_woocommerce_order_items.order_item_name
			AND mp_posts.post_author = mp_usermeta.user_id
			AND mp_usermeta.meta_key = '_wcv_store_postcode'
            AND mp_woocommerce_order_items.order_id = " .$mon_numero_commande. "
			AND mp_usermeta.user_id = " .$mon_vendeur_commande. "
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
            AND mp_woocommerce_order_items.order_id = " .$mon_numero_commande. "
			AND mp_usermeta.user_id = " .$mon_vendeur_commande. "
			AND mp_wc_order_stats.status <> 'wc-cancelled'");
	foreach ( $villevendeur as $go )
	{
		$maville = $go->Ville;
	}

	//Requete pour récupérer l'adresse mail du vendeur
	$mailVendeur = $wpdb->get_results("SELECT DISTINCT mp_wc_order_stats.date_created as 'Date', mp_woocommerce_order_items.order_item_name as Produit, mp_usermeta.meta_value as Ville, mp_wc_order_stats.status as Statut, mp_woocommerce_order_items.order_id as NumeroCommande, mp_wc_order_stats.customer_id as IdClient, mp_usermeta.user_id as IdVendeur, mp_users.user_email as MailVendeur
		FROM mp_wc_order_product_lookup, mp_wc_order_stats, mp_wc_customer_lookup, mp_woocommerce_order_items, mp_posts, mp_usermeta, mp_users
		WHERE mp_wc_order_stats.order_id= mp_wc_order_product_lookup.order_id
			AND mp_wc_order_stats.customer_id = mp_wc_customer_lookup.customer_id
			AND mp_woocommerce_order_items.order_id =  mp_wc_order_stats.order_id
			AND mp_woocommerce_order_items.order_item_type='line_item'
			AND mp_posts.post_title = mp_woocommerce_order_items.order_item_name
			AND mp_posts.post_author = mp_usermeta.user_id
			AND mp_usermeta.meta_key = '_wcv_store_city'
            AND mp_woocommerce_order_items.order_id = " .$mon_numero_commande. "
			AND mp_wc_order_stats.status <> 'wc-cancelled'
			AND mp_usermeta.user_id = " .$mon_vendeur_commande. "
            AND mp_users.ID = mp_posts.post_author");
	foreach ( $mailVendeur as $go )
	{
		$MailVendeur = $go->MailVendeur;
	}

	//Requete pour récupérer l'adresse mail du client
	$mailClient = $wpdb->get_results("SELECT DISTINCT mp_wc_order_stats.date_created as 'Date', mp_woocommerce_order_items.order_item_name as Produit, mp_usermeta.meta_value as Ville, mp_wc_order_stats.status as Statut, mp_woocommerce_order_items.order_id as NumeroCommande, mp_wc_order_stats.customer_id as IdClient, mp_usermeta.user_id as IdVendeur, mp_wc_customer_lookup.email as MailClient
		FROM mp_wc_order_product_lookup, mp_wc_order_stats, mp_wc_customer_lookup, mp_woocommerce_order_items, mp_posts, mp_usermeta, mp_users
		WHERE mp_wc_order_stats.order_id= mp_wc_order_product_lookup.order_id
			AND mp_wc_order_stats.customer_id = mp_wc_customer_lookup.customer_id
			AND mp_woocommerce_order_items.order_id =  mp_wc_order_stats.order_id
			AND mp_woocommerce_order_items.order_item_type='line_item'
			AND mp_posts.post_title = mp_woocommerce_order_items.order_item_name
			AND mp_posts.post_author = mp_usermeta.user_id
			AND mp_usermeta.meta_key = '_wcv_store_city'
            AND mp_woocommerce_order_items.order_id = " .$mon_numero_commande. "
			AND mp_usermeta.user_id = " .$mon_vendeur_commande. "
			AND mp_wc_order_stats.status <> 'wc-cancelled'
            AND mp_wc_customer_lookup.customer_id = mp_wc_order_stats.customer_id");
	foreach ( $mailClient as $go )
	{
		$MailClient = $go->MailClient;
	}

  //Requete pour récupérer code postal du client
  $codePostalClient = $wpdb->get_results("SELECT DISTINCT mp_wc_order_stats.date_created as 'Date', mp_woocommerce_order_items.order_item_name as Produit, mp_usermeta.meta_value as Ville, mp_wc_order_stats.status as Statut, mp_woocommerce_order_items.order_id as NumeroCommande, mp_wc_order_stats.customer_id as IdClient, mp_usermeta.user_id as IdVendeur, mp_wc_customer_lookup.postcode as CodePostalClient
		FROM mp_wc_order_product_lookup, mp_wc_order_stats, mp_wc_customer_lookup, mp_woocommerce_order_items, mp_posts, mp_usermeta, mp_users
		WHERE mp_wc_order_stats.order_id= mp_wc_order_product_lookup.order_id
			AND mp_wc_order_stats.customer_id = mp_wc_customer_lookup.customer_id
			AND mp_woocommerce_order_items.order_id =  mp_wc_order_stats.order_id
			AND mp_woocommerce_order_items.order_item_type='line_item'
			AND mp_posts.post_title = mp_woocommerce_order_items.order_item_name
			AND mp_posts.post_author = mp_usermeta.user_id
			AND mp_usermeta.meta_key = '_wcv_store_city'
            AND mp_woocommerce_order_items.order_id = " .$mon_numero_commande. "
			AND mp_usermeta.user_id = " .$mon_vendeur_commande. "
			AND mp_wc_order_stats.status <> 'wc-cancelled'
            AND mp_wc_customer_lookup.customer_id = mp_wc_order_stats.customer_id");
	foreach ( $codePostalClient as $go )
	{
		$CodePostalClient = $go->CodePostalClient;
	}

  //Requete pour récupérer la ville du client
  $villeClient = $wpdb->get_results("SELECT DISTINCT mp_wc_order_stats.date_created as 'Date', mp_woocommerce_order_items.order_item_name as Produit, mp_usermeta.meta_value as Ville, mp_wc_order_stats.status as Statut, mp_woocommerce_order_items.order_id as NumeroCommande, mp_wc_order_stats.customer_id as IdClient, mp_usermeta.user_id as IdVendeur, mp_wc_customer_lookup.city as VilleClient
		FROM mp_wc_order_product_lookup, mp_wc_order_stats, mp_wc_customer_lookup, mp_woocommerce_order_items, mp_posts, mp_usermeta, mp_users
		WHERE mp_wc_order_stats.order_id= mp_wc_order_product_lookup.order_id
			AND mp_wc_order_stats.customer_id = mp_wc_customer_lookup.customer_id
			AND mp_woocommerce_order_items.order_id =  mp_wc_order_stats.order_id
			AND mp_woocommerce_order_items.order_item_type='line_item'
			AND mp_posts.post_title = mp_woocommerce_order_items.order_item_name
			AND mp_posts.post_author = mp_usermeta.user_id
			AND mp_usermeta.meta_key = '_wcv_store_city'
            AND mp_woocommerce_order_items.order_id = " .$mon_numero_commande. "
			AND mp_usermeta.user_id = " .$mon_vendeur_commande. "
			AND mp_wc_order_stats.status <> 'wc-cancelled'
            AND mp_wc_customer_lookup.customer_id = mp_wc_order_stats.customer_id");
	foreach ( $villeClient as $go )
	{
		$VilleClient = $go->VilleClient;
	}

		if(creation_etiquette_mondialrelay($mon_numero_commande, $nomvendeur, $prenomclient, $nomclient, $pointrelais, $marue, $moncode, $maville, $MailVendeur, $MailClient, $mon_poids_commande, $ContenuColis, $CodePostalClient, $VilleClient)){

			// On prépare la requete SQL qui va ajouter la ligne
			$sql=$wpdb->prepare(
				"
					INSERT INTO ".$mondial_table_name."
					(order_id, id_customer, id_vendor, flag_generated, prenom_client, nom_client, point_relais, num_commande_mr, lien_etiquette, weight_in_gr, contenu_colis)
					VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)
				",
					$mon_numero_commande,
					$idclient,
					$idvendeur,
					$flag,
					$prenomclient,
					$nomclient,
					$pointrelais,
					$GLOBALS['num_expedition_mr'],
					$GLOBALS['lien_pdf_mr'],
					$mon_poids_commande,
					$ContenuColis
			);
			// On execute la requete SQL
			$wpdb->query($sql);

			echo "La commande " .$mon_numero_commande. " a bien été géneré pour le point relais :" .$pointrelais;
			die();
		}
		else{
			die();
		}
	}
	else echo "Aucun point relais n'a été sélectionné par le client lors de la commande";
	die();
}

// Fonction permettant de créer l'étiquette Mondial Relay en fonction des paramètres rentrés
function creation_etiquette_mondialrelay($numcommande, $lenomvendeur, $leprenomclient,$lenomclient, $myshopnumber, $rueVendeur, $codepostalvendeur,$villevendeur, $monMailVendeur, $monMailClient, $monPoids, $monContenuColis, $monCodePostalClient, $maVilleClient){
	// On ajoute la lib de Mondial Relay
  echo "on rentre dans la fonction de creation detiquette";
  var_dump($monCodePostalClient);
	require_once('inc/includesMondial/MondialRelay.API.Class.php');
	
	// Recup du code pays
	$shopcountry=substr($myshopnumber, 0, 2);
	//On récupère le numéro de point relais FR-XXXXXXX et on enleve le "FR-" "BE-"
	$shopnumber=substr($myshopnumber, 3);
	//$shopnumber='002683';

	//var_dump($shopnumber);

	//We declare the client
	$MRService = new MondialRelayWebAPI();


	//set the credentials
	//$MRService->_Api_CustomerCode 	= "BDTEST13"; //id test
  	$MRService->_Api_CustomerCode 	= "CC21A7Z8"; //id prod

	$MRService->_Api_BrandId 		= "41";
	//$MRService->_Api_SecretKey  	= "PrivateK"; //pass test
  	$MRService->_Api_SecretKey  	= "egtNTCJU"; //pass prod

	$MRService->_Api_Version 		= "1.0";

	$MRService->_Debug = false;

	//set the merchant adress
	//sender adress

	$merchantAdress = new Adress();
	$merchantAdress->Adress1 = $lenomvendeur;
	$merchantAdress->Adress2 = "";
	$merchantAdress->Adress3 = $rueVendeur;
	$merchantAdress->Adress4 = "";
	$merchantAdress->PostCode = $codepostalvendeur;
	$merchantAdress->City = $villevendeur;
	$merchantAdress->CountryCode = "FR";
	$merchantAdress->PhoneNumber = "+33640477052" ;
	$merchantAdress->PhoneNumber2 ="";
	$merchantAdress->Email = $monMailVendeur;
	$merchantAdress->Language = "FR";


	//-------------------------------------------------
	//Shipment Creation Sample
	//-------------------------------------------------
	//Create a new shipment object
	$myShipment = new ShipmentData();

	//set the delivery options
	$myShipment->DeliveryMode = new ShipmentInfo()  ;
	$myShipment->DeliveryMode->Mode = "24R";

	//parcel Shop ID when required
	// C'est la où le client récupère le colis / On recup depuis la selection de colis
	$myShipment->DeliveryMode->ParcelShopId = $shopnumber; //"002683";
	$myShipment->DeliveryMode->ParcelShopContryCode = $shopcountry;

	//set the pickup options
	$myShipment->CollectMode = new ShipmentInfo() ;
	//$myShipment->CollectMode->Mode = "CCC";
	$myShipment->CollectMode->Mode = "CCC";

	// A renseigner éventuellement en auto avec les ref Byfrenchyz
	$myShipment->InternalOrderReference = $numcommande;
	$myShipment->InternalCustomerReference ="BFZ";


	//sender adress with the previsously declarated adress
	$myShipment->Sender = $merchantAdress;

	//recipient adress
	$myShipment->Recipient = new Adress()  ;
	$myShipment->Recipient->Adress1 = $leprenomclient. " " . $lenomclient;
	$myShipment->Recipient->Adress2 = "";
	$myShipment->Recipient->Adress3 = "Byfrenchyz";
	$myShipment->Recipient->Adress4 = "";
	$myShipment->Recipient->PostCode = $monCodePostalClient;
	$myShipment->Recipient->City = $maVilleClient;
	$myShipment->Recipient->CountryCode = "FR";
	$myShipment->Recipient->PhoneNumber = "" ;
	$myShipment->Recipient->PhoneNumber2 = "";
	$myShipment->Recipient->Email = $monMailClient;
	$myShipment->Recipient->Language = "FR";

	//shipment datas
	$myShipment->DeliveryInstruction= "" ;
	$myShipment->CommentOnLabel= "" ;

	//parcel declaration (one item per parcel)
	$myShipment->Parcels[0] = new Parcel();
	$myShipment->Parcels[0]->WeightInGr = $monPoids;
	$myShipment->Parcels[0]->Content = $monContenuColis;

	//$myShipment->Parcels[1] = new Parcel();
	//$myShipment->Parcels[1]->WeightInGr = 2000;
	//$myShipment->Parcels[1]->Content = "pencils and paints ";

	$myShipment->InsuranceLevel="";

	$myShipment->CostOnDelivery= 0 ;
	$myShipment->CostOnDeliveryCurrency= "EUR" ;
	$myShipment->Value= 0 ;
	$myShipment->ValueCurrency= "EUR";

	//creation with Internationnal API
	$ShipmentDatas = $MRService->CreateShipment($myShipment);
	if ($ShipmentDatas->Success){
		print_r($ShipmentDatas);
		echo '<a href="'.$ShipmentDatas->LabelLink.'" >Download Stickers</a>';
		//echo "<br />";
		echo "<br />";
		$lienPdf = $ShipmentDatas->LabelLink ;
		echo 'liendump';
		$GLOBALS['lien_pdf_mr'] = $lienPdf;
		var_dump($GLOBALS['lien_pdf_mr']);

		$numero_expedition = $ShipmentDatas->ShipmentNumber;

		$GLOBALS['num_expedition_mr'] = $numero_expedition;

		// On check à l'écran le content de $numero_expedition
		//var_dump($numero_expedition);


		///envoi de mail //
		send_email_etiquette_mondial($numcommande, $numero_expedition,$lienPdf,$monMailClient,$monMailVendeur,$leprenomclient,$lenomclient,$lenomvendeur);
		return true;
		//******************************************************************FIN BLOC ENVOI DE MAIL*************************************************************/
	}
	else {
		echo "La commande n'a pas pu etre générée a cause d'une erreur";
		echo "<br />";
		print_r($ShipmentDatas);
		echo "<br />";
		return false;
	}
}


// Fonction permettant de générer la facturation
function generation_facturation(){

	// On déclare la var global de la BDD wordpress
	global $wpdb ;
	date_default_timezone_set("Europe/Paris");
	$maDate = date('d-m-Y');

	// On récupère le préfix et on y ajoute le nom de table 'mondialrelay'
	$mondial_table_name = $wpdb->prefix . 'mondialrelay';

	$myMangoAction = new Mangopay;

	// On rentre en dur l'id et le wallet du user crédité : Byfrenchyz
	//$myMangoAction->transfert_wallet($_POST['idVendorMangoPay'], 66936973, $_POST['the_amount_to_transfer'], $_POST['numWalletVendorMangoPay'], 72148808, $_POST['orderId']);
  try{

    $myMangoAction->take_mondialrelay_fees($_POST['idVendorMangoPay'], $_POST['the_amount_to_transfer'], $_POST['numWalletVendorMangoPay'], $_POST['orderId']);
  	$nomVendeur = get_vendor_name($_POST['idVendorBase']);
  	// On prépare la requete SQL qui va ajouter la ligne
  	$sql=$wpdb->prepare(
  		"
  			UPDATE $mondial_table_name
  			SET paid = 'yes' , amount_shipping = ".$_POST['amountForBase']." , time_paid = NOW()
  			WHERE order_id = ".$_POST['orderId']."
  			AND id_vendor = ".$_POST['idVendorBase']."
  		",

  	);
  	// On execute la requete SQL
  	$wpdb->query($sql);

    // On envoie le mail de notification de prélèvement de l'expédition Mondial Relay
    send_email_facturation_expedition($_POST['vendor_email'],$_POST['orderId'], $_POST['numero_mr'], $_POST['amountForBase'], $nomVendeur, $maDate);
    
  } catch(Exception $e){
    print_r($e);
  }

}
?>
