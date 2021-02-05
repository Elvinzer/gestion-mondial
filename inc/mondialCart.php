<?php
    // Ajout de l'image de Mangopay dans la validation de la commande en bas de la selection de paiement par CB
    add_action('woocommerce_checkout_after_order_review','add_mango_image');
    function add_mango_image(){
        $imgsrc = get_stylesheet_directory_uri().'/images/mangopay-terms.png';
        echo "<img src='" . $imgsrc . "' alt='Mangopay partnerimage' />";
    }

    add_action('woocommerce_checkout_before_customer_details','add_selection_point_relais');
    
    function add_selection_point_relais(){
        echo "<h3 style= 'text-align: center;'>Sélection Point Relais</h3>";
        wp_enqueue_script( 'jquerymin', 'https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js', array('jquery'), '1.0', true );
        wp_enqueue_script( 'cart', plugins_url() . '/gestion-mondial/js/cart.js', array('jquery'), '1.0', true );
        wp_enqueue_script( 'parcelpicker', 'https://widget.mondialrelay.com/parcelshop-picker/jquery.plugin.mondialrelay.parcelshoppicker.min.js', array('jquerymin'), '1.0', true );
        
        echo "<div style='margin-bottom: 10px;' id='Zone_Widget_MondialRelay'></div>";
    }