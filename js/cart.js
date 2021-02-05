// Zone Mondial Relay rajouté dans la page de paiement
$(document).ready(function () {  
    $("#Zone_Widget_MondialRelay").MR_ParcelShopPicker({
      // L'id de la target correspond à un form mis en invisible et met la valeur dans mp_postmeta.meta_key = 'point_relais'
      Target: "#point_relais",
      TargetDisplay: "#TargetDisplay_Widget",
      TargetDisplayInfoPR: "#TargetDisplayInfoPR_Widget",
      Brand: "CC21A7Z8",
      ColLivMod: "24R",
      Country: "FR" ,
      Responsive: true,  
      ShowResultsOnMap: true
    });  
  });