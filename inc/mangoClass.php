<?php
	class Mangopay{
		private $login;
		private $password;
		private $headers;
		private $pre_url;

		//On initialise nos logins dans le constructeur de la classe
		public function __construct(){

			$this->login = 'byfrenchyzprod';
			$this->password = 'nbtJQkYzrARSjmABVQ93E68ZwVRr98LUuaWs46q7Y6J0xYAeh2';
			$this->pre_url = 'https://api.mangopay.com/v2.01/';

			$this->headers = array(
				"Authorization" => 'Basic ' . base64_encode( $this->login . ':' . $this->password  ),
				'Cache-Control' => 'no-cache',
				'Content-Type' => 'application/json',
				'Host' => 'api.mangopay.com',
			);
		}

		// This function only take EUR currency / C'est la fonction permettant de faire des transferts de Wallet en Wallet directement dans le portefeuille fees de Byfrenchyz
		function take_mondialrelay_fees($owner_debited_id, $amount, $wallet_debited_id, $myOrderId){

			$url_transfer = $this->pre_url . $this->login . '/transfers/';
			//Wallet "passerelle" Mondial Relay
			$wallet_credited_id = 1073729084;

			//On crée la requete de l'API Mangopay
			$request_transfer = '{
				"AuthorId": "' .$owner_debited_id. '",
				"DebitedFunds": {
					 "Currency": "EUR",
					 "Amount": ' .$amount. '
				},
				"Fees": {
					 "Currency": "EUR",
					 "Amount": ' .$amount. '
				},
				"DebitedWalletId": "' .$wallet_debited_id. '",
				"CreditedWalletId": "' .$wallet_credited_id. '",
				"Tag": "Prélèvement expédition commande: ' .$myOrderId. '"
			}';


			//On execute la requete API via la fonction wp_remote_post de Wordpress
			$post_create = wp_remote_post( $url_transfer,
				array(
					'method'    => 'POST',
					'headers'   => $this->headers,
					'body' => $request_transfer

			));
			echo '<div> ' . print_r($post_create) . '</div>';

		}

		// This function only take EUR currency / C'est la fonction permettant de faire des transferts de Wallet en Wallet
		function transfert_wallet($owner_debited_id, $credited_user_id, $amount, $wallet_debited_id, $wallet_credited_id, $myOrderId){

			$url_transfer = $this->pre_url . $this->login . '/transfers/';


			//On crée la requete de l'API Mangopay
			$request_transfer = '{
				"AuthorId": "' .$owner_debited_id. '",
				"DebitedFunds": {
				   "Currency": "EUR",
				   "Amount": ' .$amount. '
				},
				"Fees": {
				   "Currency": "EUR",
				   "Amount": 0
				},
				"DebitedWalletId": "' .$wallet_debited_id. '",
				"CreditedWalletId": "' .$wallet_credited_id. '",
				"Tag": "Prélèvement expédition commande: ' .$myOrderId. '"
			}';


			//On execute la requete API via la fonction wp_remote_post de Wordpress
			$post_create = wp_remote_post( $url_transfer,
				array(
					'method'    => 'POST',
					'headers'   => $this->headers,
					'body' => $request_transfer

			));
			echo '<div> ' . print_r($post_create) . '</div>';

		}

		// Fonction permettant de récupérer l'id situé côté Mangopay via l'API
		function get_user_id_mango($mail_user){

			// Ceci est l'URL Mangopay correspondant à la requete d'afficher tous les users
			$url = $this->pre_url . $this->login . '/users/';
			// On récupère le nombre total de page dans la réponse de la requete
			$number_of_page = $this->get_number_of_page($url);

			// On parcourt toutes les pages
			for ($page_number = 1; $page_number <= $number_of_page; $page_number++) {
				$url_id = $this->pre_url .$this->login . '/users/?page=' . $page_number;
				$myTotalResponse = wp_remote_retrieve_body(wp_remote_request($url_id,
					array(
					  'headers'   => $this->headers,
					  'body' => $url_id,
				)));

				//On décode la réponse JSON en Array compréhensible par PHP
				$total_reponse_decoded = json_decode($myTotalResponse);

				for ($j = 0; $j < sizeof($total_reponse_decoded); $j++) {
					if($total_reponse_decoded[$j]->{'Email'} == $mail_user){
						return $total_reponse_decoded[$j]->{'Id'};
					}
				}
			}
		}

		// Fonction permettant de récupérer le nombre de page contenu dans une requete API
		function get_number_of_page($myRequest){

			$myTotalResponse = wp_remote_request($myRequest,
				array(
				  'headers'   => $this->headers,
				  'body' => $myRequest
			));
			// On récupère uniquement le header et plus particulièrement la valeur de 'X-Number-Of-Pages'
			$monNombreDePage = wp_remote_retrieve_header($myTotalResponse, 'X-Number-Of-Pages');
			return $monNombreDePage;
		}

		//Fonction permettant de récupérer l'adresse mail du vendeur stockée en base Byfrenchyz
		public function get_vendor_email($vendor_id){
			global $wpdb;

			$mondial_table_name = $wpdb->prefix . 'mondialrelay';

			$mailVendeur = $wpdb->get_results("SELECT mp_users.user_email as 'MailVendeur' FROM $mondial_table_name , mp_users
			WHERE mp_mondialrelay.id_vendor = " .$vendor_id. "
			AND mp_mondialrelay.id_vendor = mp_users.ID");

			foreach ( $mailVendeur as $go )
			{
				$MailVendeur = $go->MailVendeur;
			}

			return $MailVendeur;
		}

		// C'est la fonction permettant de récupérer le mail correspondant à un id
		function get_wallet($user_id){

			// Ceci est l'URL Mangopay correspondant à la requete d'afficher un user donné
			$url_transfer = $this->pre_url .$this->login . '/users/' .$user_id. '/wallets/';

			//On veut récupérer que le body de la réponse avec la fonction "wp_remote_retrieve_body"
			$myResponse = wp_remote_retrieve_body(wp_remote_request($url_transfer,
				array(
				  'headers'   => $this->headers,
				  'body' => $request_transfer,
			)));
			//On décode la réponse JSON en Array compréhensible par PHP
			$reponse_decoded = json_decode($myResponse);
			foreach ( $reponse_decoded as $wallet )
			{
				$monwallet = $monwallet . $wallet->{'Id'};
			}
				return $monwallet;
		}

	}
?>
